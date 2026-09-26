<?php
// includes/accounting_functions.php - Double-Entry Accounting Core Engine

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

// Check Accountant Login Session
function checkAccountantLogin() {
    if (!isset($_SESSION['accountant_id'])) {
        header("Location: " . getBaseUrl() . "/accountant_login.php");
        exit;
    }
}

// Get Logged In Accountant
function getLoggedInAccountant() {
    if (!isset($_SESSION['accountant_id'])) return null;
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM accountants WHERE id = ?");
    $stmt->execute([$_SESSION['accountant_id']]);
    return $stmt->fetch();
}

// Immutable Audit Logging
function logAccountingAudit($pdo, $action_type, $target_entity, $target_id, $description) {
    $accountant_id = $_SESSION['accountant_id'] ?? null;
    $username = $_SESSION['accountant_username'] ?? 'System';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    $stmt = $pdo->prepare("INSERT INTO accounting_audit_logs (accountant_id, username, action_type, target_entity, target_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$accountant_id, $username, $action_type, $target_entity, (string)$target_id, $description, $ip_address]);
}

// Generate Next Unique Voucher Number (e.g. REC-2025-0001, PAY-2025-0001, CON-2025-0001, JRN-2025-0001)
function generateVoucherNumber($pdo, $voucher_type) {
    $prefixes = [
        'Receipt' => 'REC',
        'Payment' => 'PAY',
        'Contra' => 'CON',
        'Journal' => 'JRN',
        'Debit_Note' => 'DBN',
        'Credit_Note' => 'CRN'
    ];

    $prefix = $prefixes[$voucher_type] ?? 'VOU';
    $year = date('Y');

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vouchers WHERE voucher_type = ? AND YEAR(voucher_date) = ?");
    $stmt->execute([$voucher_type, $year]);
    $seq = (int)$stmt->fetchColumn() + 1;

    do {
        $voucher_num = sprintf("%s-%s-%04d", $prefix, $year, $seq);
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM vouchers WHERE voucher_number = ?");
        $stmt_check->execute([$voucher_num]);
        $exists = (int)$stmt_check->fetchColumn();
        if ($exists > 0) $seq++;
    } while ($exists > 0);

    return $voucher_num;
}

// Generate Next Unique Commercial Document Number (e.g. INV-2025-0001, PUR-2025-0001)
function generateDocumentNumber($pdo, $doc_type) {
    $prefixes = [
        'Purchase_Order' => 'PO',
        'Purchase_Bill' => 'PB',
        'Sales_Quotation' => 'QT',
        'Tax_Invoice' => 'INV',
        'Sales_Return' => 'SRN',
        'Purchase_Return' => 'PRN'
    ];

    $prefix = $prefixes[$doc_type] ?? 'DOC';
    $year = date('Y');

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM commercial_documents WHERE doc_type = ? AND YEAR(doc_date) = ?");
    $stmt->execute([$doc_type, $year]);
    $seq = (int)$stmt->fetchColumn() + 1;

    do {
        $doc_num = sprintf("%s-%s-%04d", $prefix, $year, $seq);
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM commercial_documents WHERE doc_number = ?");
        $stmt_check->execute([$doc_num]);
        $exists = (int)$stmt_check->fetchColumn();
        if ($exists > 0) $seq++;
    } while ($exists > 0);

    return $doc_num;
}

// Recalculate Current Balance for a COA Account Head
function syncAccountBalance($pdo, $account_id) {
    $stmt = $pdo->prepare("SELECT type, opening_balance, opening_balance_type FROM accounts_coa WHERE id = ?");
    $stmt->execute([$account_id]);
    $acc = $stmt->fetch();
    if (!$acc) return;

    $opening = (float)$acc['opening_balance'];
    $opening_type = $acc['opening_balance_type'];

    // Sum Debits and Credits from posted voucher line items
    $stmt_deb = $pdo->prepare("SELECT SUM(amount) FROM voucher_line_items vli JOIN vouchers v ON vli.voucher_id = v.id WHERE vli.account_id = ? AND vli.line_type = 'Debit' AND v.status = 'Posted'");
    $stmt_deb->execute([$account_id]);
    $debits = (float)($stmt_deb->fetchColumn() ?: 0.00);

    $stmt_crd = $pdo->prepare("SELECT SUM(amount) FROM voucher_line_items vli JOIN vouchers v ON vli.voucher_id = v.id WHERE vli.account_id = ? AND vli.line_type = 'Credit' AND v.status = 'Posted'");
    $stmt_crd->execute([$account_id]);
    $credits = (float)($stmt_crd->fetchColumn() ?: 0.00);

    // Assets & Expenses increase on Debit; Liabilities, Equity & Revenues increase on Credit
    if (in_array($acc['type'], ['Asset', 'Expense'])) {
        $opening_adj = ($opening_type === 'Debit') ? $opening : -$opening;
        $current = $opening_adj + $debits - $credits;
    } else {
        $opening_adj = ($opening_type === 'Credit') ? $opening : -$opening;
        $current = $opening_adj + $credits - $debits;
    }

    $stmt_up = $pdo->prepare("UPDATE accounts_coa SET current_balance = ? WHERE id = ?");
    $stmt_up->execute([$current, $account_id]);
}

// ACID-Compliant Double-Entry Voucher Posting Logic
function postVoucher($pdo, $voucher_type, $voucher_date, $narration, $reference_number, $line_items) {
    if (empty($line_items) || count($line_items) < 2) {
        throw new Exception("Double-entry voucher must contain at least two line items (Debit and Credit).");
    }

    $total_debit = 0.00;
    $total_credit = 0.00;

    foreach ($line_items as $item) {
        $amount = round((float)$item['amount'], 2);
        if ($amount <= 0) {
            throw new Exception("Voucher line amount must be greater than zero.");
        }
        if ($item['line_type'] === 'Debit') {
            $total_debit += $amount;
        } else {
            $total_credit += $amount;
        }
    }

    // MANDATORY DOUBLE-ENTRY VALIDATION: Debits MUST Equal Credits
    if (abs($total_debit - $total_credit) > 0.01) {
        throw new Exception(sprintf("Unbalanced Entry: Total Debits (₹%.2f) must equal Total Credits (₹%.2f).", $total_debit, $total_credit));
    }

    $accountant_id = $_SESSION['accountant_id'] ?? 1;
    $voucher_number = generateVoucherNumber($pdo, $voucher_type);

    $stmt = $pdo->prepare("INSERT INTO vouchers (voucher_number, voucher_type, voucher_date, narration, reference_number, total_debit, total_credit, status, created_by_accountant_id) VALUES (?, ?, ?, ?, ?, ?, ?, 'Posted', ?)");
    $stmt->execute([$voucher_number, $voucher_type, $voucher_date, $narration, $reference_number, $total_debit, $total_credit, $accountant_id]);
    $voucher_id = $pdo->lastInsertId();

    $stmt_line = $pdo->prepare("INSERT INTO voucher_line_items (voucher_id, account_id, line_type, amount, particulars) VALUES (?, ?, ?, ?, ?)");

    $affected_accounts = [];
    foreach ($line_items as $item) {
        $stmt_line->execute([
            $voucher_id,
            $item['account_id'],
            $item['line_type'],
            round((float)$item['amount'], 2),
            $item['particulars'] ?? $narration
        ]);
        $affected_accounts[$item['account_id']] = true;
    }

    // Sync all affected COA ledger balances
    foreach (array_keys($affected_accounts) as $acc_id) {
        syncAccountBalance($pdo, $acc_id);
    }

    logAccountingAudit($pdo, 'VOUCHER_CREATE', 'Voucher', $voucher_id, "Posted {$voucher_type} Voucher #{$voucher_number} for ₹{$total_debit}");

    return [
        'voucher_id' => $voucher_id,
        'voucher_number' => $voucher_number
    ];
}

// Reverse / Void an Existing Voucher
function reverseVoucher($pdo, $voucher_id, $reason) {
    $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE id = ?");
    $stmt->execute([$voucher_id]);
    $v = $stmt->fetch();

    if (!$v) {
        throw new Exception("Voucher record not found.");
    }

    if ($v['status'] === 'Reversed') {
        throw new Exception("Voucher #{$v['voucher_number']} is already reversed.");
    }

    // Mark status as Reversed
    $stmt_up = $pdo->prepare("UPDATE vouchers SET status = 'Reversed' WHERE id = ?");
    $stmt_up->execute([$voucher_id]);

    // Re-sync all affected accounts
    $stmt_accs = $pdo->prepare("SELECT DISTINCT account_id FROM voucher_line_items WHERE voucher_id = ?");
    $stmt_accs->execute([$voucher_id]);
    $accs = $stmt_accs->fetchAll(PDO::FETCH_COLUMN);

    foreach ($accs as $acc_id) {
        syncAccountBalance($pdo, $acc_id);
    }

    logAccountingAudit($pdo, 'VOUCHER_REVERSE', 'Voucher', $voucher_id, "Reversed Voucher #{$v['voucher_number']}. Reason: {$reason}");

    return true;
}

// Post Commercial Document (Purchase Bill / Tax Invoice) with GST and Auto-Inventory Linkage
function postCommercialDocument($pdo, $doc_type, $doc_date, $party_id, $items, $notes = '') {
    if (empty($items)) {
        throw new Exception("Commercial document must contain at least one item.");
    }

    $stmt_p = $pdo->prepare("SELECT * FROM parties WHERE id = ?");
    $stmt_p->execute([$party_id]);
    $party = $stmt_p->fetch();

    if (!$party) {
        throw new Exception("Invalid Customer / Vendor party selected.");
    }

    $is_interstate = ($party['state_code'] !== '27'); // 27 = Maharashtra (Company Home State)
    $doc_number = generateDocumentNumber($pdo, $doc_type);

    $subtotal = 0.00;
    $total_cgst = 0.00;
    $total_sgst = 0.00;
    $total_igst = 0.00;

    $processed_items = [];

    foreach ($items as $itm) {
        $qty = (float)$itm['qty'];
        $rate = (float)$itm['rate'];
        $taxable = round($qty * $rate, 2);

        $stmt_i = $pdo->prepare("SELECT * FROM inventory_items WHERE id = ?");
        $stmt_i->execute([$itm['item_id']]);
        $inv_item = $stmt_i->fetch();

        if (!$inv_item) {
            throw new Exception("Inventory item #{$itm['item_id']} not found.");
        }

        $gst_rate = (float)$inv_item['gst_rate'];

        if ($is_interstate) {
            $cgst = 0.00;
            $sgst = 0.00;
            $igst = round($taxable * ($gst_rate / 100), 2);
        } else {
            $cgst = round($taxable * (($gst_rate / 2) / 100), 2);
            $sgst = round($taxable * (($gst_rate / 2) / 100), 2);
            $igst = 0.00;
        }

        $line_total = $taxable + $cgst + $sgst + $igst;

        $subtotal += $taxable;
        $total_cgst += $cgst;
        $total_sgst += $sgst;
        $total_igst += $igst;

        $processed_items[] = [
            'item_id' => $inv_item['id'],
            'qty' => $qty,
            'rate' => $rate,
            'taxable_value' => $taxable,
            'hsn_sac' => $inv_item['hsn_sac'],
            'gst_rate' => $gst_rate,
            'cgst' => $cgst,
            'sgst' => $sgst,
            'igst' => $igst,
            'line_total' => $line_total
        ];
    }

    $grand_total = $subtotal + $total_cgst + $total_sgst + $total_igst;
    $accountant_id = $_SESSION['accountant_id'] ?? 1;

    // Save Commercial Document Record
    $stmt_doc = $pdo->prepare("INSERT INTO commercial_documents (doc_number, doc_type, doc_date, party_id, place_of_supply, state_code, is_interstate, subtotal, total_cgst, total_sgst, total_igst, grand_total, created_by_accountant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_doc->execute([
        $doc_number,
        $doc_type,
        $doc_date,
        $party_id,
        $party['state_name'],
        $party['state_code'],
        $is_interstate ? 1 : 0,
        $subtotal,
        $total_cgst,
        $total_sgst,
        $total_igst,
        $grand_total,
        $accountant_id
    ]);
    $doc_id = $pdo->lastInsertId();

    $stmt_ditem = $pdo->prepare("INSERT INTO commercial_document_items (document_id, item_id, qty, rate, taxable_value, hsn_sac, gst_rate, cgst_amount, sgst_amount, igst_amount, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($processed_items as $pi) {
        $stmt_ditem->execute([
            $doc_id,
            $pi['item_id'],
            $pi['qty'],
            $pi['rate'],
            $pi['taxable_value'],
            $pi['hsn_sac'],
            $pi['gst_rate'],
            $pi['cgst'],
            $pi['sgst'],
            $pi['igst'],
            $pi['line_total']
        ]);

        // Auto-Update Inventory Qty & Log Inventory Transaction
        if (in_array($doc_type, ['Purchase_Bill', 'Sales_Return'])) {
            $stmt_qty = $pdo->prepare("UPDATE inventory_items SET current_qty = current_qty + ? WHERE id = ?");
            $stmt_qty->execute([$pi['qty'], $pi['item_id']]);

            $stmt_log = $pdo->prepare("INSERT INTO inventory_transactions (item_id, trans_date, trans_type, reference_id, qty, rate, valuation_total) VALUES (?, ?, 'Purchase', ?, ?, ?, ?)");
            $stmt_log->execute([$pi['item_id'], $doc_date, $doc_id, $pi['qty'], $pi['rate'], $pi['taxable_value']]);
        } elseif (in_array($doc_type, ['Tax_Invoice', 'Purchase_Return'])) {
            $stmt_qty = $pdo->prepare("UPDATE inventory_items SET current_qty = current_qty - ? WHERE id = ?");
            $stmt_qty->execute([$pi['qty'], $pi['item_id']]);

            $stmt_log = $pdo->prepare("INSERT INTO inventory_transactions (item_id, trans_date, trans_type, reference_id, qty, rate, valuation_total) VALUES (?, ?, 'Sales', ?, ?, ?, ?)");
            $stmt_log->execute([$pi['item_id'], $doc_date, $doc_id, $pi['qty'], $pi['rate'], $pi['taxable_value']]);
        }
    }

    // Auto-Post Accounting Double-Entry Voucher
    $voucher_lines = [];

    if ($doc_type === 'Tax_Invoice') {
        // Debit Sundry Debtor Ledger
        $voucher_lines[] = ['account_id' => $party['account_id'], 'line_type' => 'Debit', 'amount' => $grand_total, 'particulars' => "Sales Invoice {$doc_number} to {$party['name']}"];
        // Credit Sales Revenue Account (4000)
        $voucher_lines[] = ['account_id' => 14, 'line_type' => 'Credit', 'amount' => $subtotal, 'particulars' => "Taxable Sales Value"];

        if ($total_cgst > 0) {
            $voucher_lines[] = ['account_id' => 9, 'line_type' => 'Credit', 'amount' => $total_cgst, 'particulars' => "Output CGST"];
        }
        if ($total_sgst > 0) {
            $voucher_lines[] = ['account_id' => 10, 'line_type' => 'Credit', 'amount' => $total_sgst, 'particulars' => "Output SGST"];
        }
        if ($total_igst > 0) {
            $voucher_lines[] = ['account_id' => 11, 'line_type' => 'Credit', 'amount' => $total_igst, 'particulars' => "Output IGST"];
        }

        $v_res = postVoucher($pdo, 'Receipt', $doc_date, "Tax Invoice {$doc_number}", $doc_number, $voucher_lines);
        $v_id = $v_res['voucher_id'];

        // Link Voucher to Document
        $stmt_uv = $pdo->prepare("UPDATE commercial_documents SET voucher_id = ? WHERE id = ?");
        $stmt_uv->execute([$v_id, $doc_id]);

        // Log Tax Transaction for GSTR-1
        $stmt_tax = $pdo->prepare("INSERT INTO tax_transactions (trans_date, voucher_id, document_id, party_id, gstin, tax_type, supply_type, taxable_value, tax_rate, tax_amount) VALUES (?, ?, ?, ?, ?, ?, 'Outward', ?, ?, ?)");
        if ($total_cgst > 0) $stmt_tax->execute([$doc_date, $v_id, $doc_id, $party_id, $party['gstin'], 'CGST', $subtotal, $gst_rate/2, $total_cgst]);
        if ($total_sgst > 0) $stmt_tax->execute([$doc_date, $v_id, $doc_id, $party_id, $party['gstin'], 'SGST', $subtotal, $gst_rate/2, $total_sgst]);
        if ($total_igst > 0) $stmt_tax->execute([$doc_date, $v_id, $doc_id, $party_id, $party['gstin'], 'IGST', $subtotal, $gst_rate, $total_igst]);

    } elseif ($doc_type === 'Purchase_Bill') {
        // Debit Purchase Account (5000)
        $voucher_lines[] = ['account_id' => 16, 'line_type' => 'Debit', 'amount' => $subtotal, 'particulars' => "Purchase Value"];

        if ($total_cgst > 0) {
            $voucher_lines[] = ['account_id' => 4, 'line_type' => 'Debit', 'amount' => $total_cgst, 'particulars' => "Input CGST"];
        }
        if ($total_sgst > 0) {
            $voucher_lines[] = ['account_id' => 5, 'line_type' => 'Debit', 'amount' => $total_sgst, 'particulars' => "Input SGST"];
        }
        if ($total_igst > 0) {
            $voucher_lines[] = ['account_id' => 6, 'line_type' => 'Debit', 'amount' => $total_igst, 'particulars' => "Input IGST"];
        }

        // Credit Sundry Creditor Ledger
        $voucher_lines[] = ['account_id' => $party['account_id'], 'line_type' => 'Credit', 'amount' => $grand_total, 'particulars' => "Purchase Bill {$doc_number} from {$party['name']}"];

        $v_res = postVoucher($pdo, 'Payment', $doc_date, "Purchase Bill {$doc_number}", $doc_number, $voucher_lines);
        $v_id = $v_res['voucher_id'];

        $stmt_uv = $pdo->prepare("UPDATE commercial_documents SET voucher_id = ? WHERE id = ?");
        $stmt_uv->execute([$v_id, $doc_id]);

        // Log Tax Transaction for GSTR-3B ITC
        $stmt_tax = $pdo->prepare("INSERT INTO tax_transactions (trans_date, voucher_id, document_id, party_id, gstin, tax_type, supply_type, taxable_value, tax_rate, tax_amount) VALUES (?, ?, ?, ?, ?, ?, 'Inward', ?, ?, ?)");
        if ($total_cgst > 0) $stmt_tax->execute([$doc_date, $v_id, $doc_id, $party_id, $party['gstin'], 'CGST', $subtotal, $gst_rate/2, $total_cgst]);
        if ($total_sgst > 0) $stmt_tax->execute([$doc_date, $v_id, $doc_id, $party_id, $party['gstin'], 'SGST', $subtotal, $gst_rate/2, $total_sgst]);
        if ($total_igst > 0) $stmt_tax->execute([$doc_date, $v_id, $doc_id, $party_id, $party['gstin'], 'IGST', $subtotal, $gst_rate, $total_igst]);
    }

    logAccountingAudit($pdo, 'DOC_POST', 'Commercial_Document', $doc_id, "Posted {$doc_type} #{$doc_number} for ₹{$grand_total}");

    return [
        'document_id' => $doc_id,
        'doc_number' => $doc_number,
        'grand_total' => $grand_total
    ];
}

// Indian Financial Year Rollover Tool (Carrying Closing Balances from March 31 to April 01)
function performFinancialYearRollover($pdo, $from_fy_year, $to_fy_year) {
    // FY format e.g. 2024 (April 1, 2024 to March 31, 2025) -> 2025 (April 1, 2025 to March 31, 2026)
    $from_start = "{$from_fy_year}-04-01";
    $from_end = ($from_fy_year + 1) . "-03-31";

    $stmt_coas = $pdo->query("SELECT * FROM accounts_coa");
    $coas = $stmt_coas->fetchAll(PDO::FETCH_ASSOC);

    $rollover_count = 0;

    foreach ($coas as $acc) {
        $acc_id = $acc['id'];
        syncAccountBalance($pdo, $acc_id);

        $stmt_curr = $pdo->prepare("SELECT current_balance, type FROM accounts_coa WHERE id = ?");
        $stmt_curr->execute([$acc_id]);
        $cur_data = $stmt_curr->fetch();

        $balance = (float)$cur_data['current_balance'];

        // Assets & Equity/Liability carry forward opening balances
        if (in_array($cur_data['type'], ['Asset', 'Liability', 'Equity'])) {
            $opening_type = ($cur_data['type'] === 'Asset') ? 'Debit' : 'Credit';
            $stmt_up = $pdo->prepare("UPDATE accounts_coa SET opening_balance = ?, opening_balance_type = ? WHERE id = ?");
            $stmt_up->execute([abs($balance), $opening_type, $acc_id]);
            $rollover_count++;
        } else {
            // P&L Accounts (Revenue & Expense) reset to 0 opening balance for new FY
            $stmt_up = $pdo->prepare("UPDATE accounts_coa SET opening_balance = 0.00 WHERE id = ?");
            $stmt_up->execute([$acc_id]);
        }
    }

    logAccountingAudit($pdo, 'FY_ROLLOVER', 'System', 0, "Executed Financial Year Rollover from FY {$from_fy_year}-" . ($from_fy_year+1) . " to FY {$to_fy_year}-" . ($to_fy_year+1));

    return $rollover_count;
}
