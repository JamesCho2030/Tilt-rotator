-- 기본 관리자 계정 (비밀번호: Admin1234!)
INSERT INTO users (email, password, name, department, position, role, approval_limit, is_active) VALUES
('admin@jkinnovation.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '시스템 관리자', '경영지원팀', '이사', 'ADMIN', 999999999.99, 1),
('buyer@jkinnovation.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '구매담당자', '구매팀', '대리', 'BUYER', 0, 1),
('manager@jkinnovation.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '팀장', '구매팀', '팀장', 'APPROVER', 1000000.00, 1),
('director@jkinnovation.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '부서장', '구매팀', '부장', 'APPROVER', 5000000.00, 1);

-- 샘플 공급업체
INSERT INTO suppliers (supplier_code, supplier_name, business_number, representative, phone, email, address, payment_terms, payment_days, rating) VALUES
('SUP-001', '(주)한국자재', '123-45-67890', '김철수', '02-1234-5678', 'sales@hankook.com', '서울시 강남구 테헤란로 123', '현금', 30, 4.50),
('SUP-002', '대한부품', '234-56-78901', '이영희', '031-2345-6789', 'info@daehan.com', '경기도 성남시 분당구 판교로 456', '어음', 60, 4.20),
('SUP-003', '글로벌산업자재', '345-67-89012', '박민수', '051-3456-7890', 'contact@global.com', '부산시 해운대구 센텀로 789', '현금', 15, 4.80);

-- 샘플 제품
INSERT INTO products (product_code, product_name, specification, unit, category, safety_stock, reorder_point, created_by) VALUES
('PROD-001', '강판 SUS304', '1.0T x 1000 x 2000', 'EA', '원자재', 50, 30, 1),
('PROD-002', '볼트 M8x20', 'SUS304, 육각머리', 'EA', '부품', 500, 300, 1),
('PROD-003', '베어링 6204', '내경20mm, 외경47mm', 'EA', '부품', 100, 50, 1),
('PROD-004', '실리콘 고무판', '3T x 500 x 500, Shore A60', 'EA', '원자재', 30, 20, 1),
('PROD-005', '케이블 타이', '300mm, 흰색', 'EA', '부자재', 1000, 500, 1);

-- 제품-공급업체 연결
INSERT INTO product_suppliers (product_id, supplier_id, unit_price, min_order_qty, lead_time_days, is_preferred) VALUES
(1, 1, 35000.00, 10, 7, 1),
(1, 3, 33000.00, 20, 10, 0),
(2, 2, 50.00, 100, 5, 1),
(3, 2, 3500.00, 10, 7, 1),
(4, 1, 25000.00, 5, 7, 1),
(5, 3, 10.00, 500, 3, 1);

-- 초기 재고 설정
INSERT INTO inventory (product_id, warehouse_location, quantity, unit_cost) VALUES
(1, 'MAIN', 45, 34000.00),
(2, 'MAIN', 450, 48.00),
(3, 'MAIN', 80, 3400.00),
(4, 'MAIN', 25, 24000.00),
(5, 'MAIN', 800, 9.50);
