DELIMITER $$

-- 트리거: 발주서 총액 자동 계산
CREATE TRIGGER trg_update_po_total AFTER INSERT ON purchase_order_items
FOR EACH ROW
BEGIN
    UPDATE purchase_orders 
    SET total_amount = (
        SELECT IFNULL(SUM(amount), 0) FROM purchase_order_items 
        WHERE purchase_order_id = NEW.purchase_order_id
    ),
    vat_amount = (
        SELECT IFNULL(SUM(amount), 0) * 0.1 FROM purchase_order_items 
        WHERE purchase_order_id = NEW.purchase_order_id
    )
    WHERE id = NEW.purchase_order_id;
END$$

CREATE TRIGGER trg_update_po_total_on_update AFTER UPDATE ON purchase_order_items
FOR EACH ROW
BEGIN
    UPDATE purchase_orders 
    SET total_amount = (
        SELECT IFNULL(SUM(amount), 0) FROM purchase_order_items 
        WHERE purchase_order_id = NEW.purchase_order_id
    ),
    vat_amount = (
        SELECT IFNULL(SUM(amount), 0) * 0.1 FROM purchase_order_items 
        WHERE purchase_order_id = NEW.purchase_order_id
    )
    WHERE id = NEW.purchase_order_id;
END$$

-- 트리거: 재고 자동 업데이트
CREATE TRIGGER trg_inventory_transaction AFTER INSERT ON inventory_transactions
FOR EACH ROW
BEGIN
    DECLARE current_qty DECIMAL(15,2);
    
    -- 현재 재고 확인
    SELECT quantity INTO current_qty 
    FROM inventory 
    WHERE product_id = NEW.product_id 
    AND warehouse_location = NEW.warehouse_location;
    
    -- 재고 업데이트
    IF current_qty IS NOT NULL THEN
        IF NEW.transaction_type = 'IN' THEN
            UPDATE inventory 
            SET quantity = quantity + NEW.quantity,
                last_updated_at = CURRENT_TIMESTAMP
            WHERE product_id = NEW.product_id 
            AND warehouse_location = NEW.warehouse_location;
        ELSEIF NEW.transaction_type = 'OUT' THEN
            UPDATE inventory 
            SET quantity = quantity - NEW.quantity,
                last_updated_at = CURRENT_TIMESTAMP
            WHERE product_id = NEW.product_id 
            AND warehouse_location = NEW.warehouse_location;
        ELSEIF NEW.transaction_type = 'ADJUST' THEN
            UPDATE inventory 
            SET quantity = NEW.quantity,
                last_updated_at = CURRENT_TIMESTAMP
            WHERE product_id = NEW.product_id 
            AND warehouse_location = NEW.warehouse_location;
        END IF;
    ELSE
        -- 재고 레코드가 없으면 생성
        INSERT INTO inventory (product_id, warehouse_location, quantity)
        VALUES (NEW.product_id, NEW.warehouse_location, NEW.quantity);
    END IF;
END$$

-- 프로시저: 발주번호 생성
CREATE PROCEDURE sp_generate_po_number(OUT po_num VARCHAR(50))
BEGIN
    DECLARE today_date VARCHAR(8);
    DECLARE seq INT;
    
    SET today_date = DATE_FORMAT(CURDATE(), '%Y%m%d');
    
    SELECT IFNULL(MAX(CAST(SUBSTRING(po_number, 12) AS UNSIGNED)), 0) + 1 
    INTO seq
    FROM purchase_orders 
    WHERE po_number LIKE CONCAT('PO-', today_date, '-%');
    
    SET po_num = CONCAT('PO-', today_date, '-', LPAD(seq, 4, '0'));
END$$

DELIMITER ;
