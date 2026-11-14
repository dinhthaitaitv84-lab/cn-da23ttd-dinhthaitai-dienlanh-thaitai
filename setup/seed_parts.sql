USE dienlanh_db;

INSERT INTO parts (name, sku, unit, price, stock_qty, status)
VALUES
    ('Gas R32', 'GAS-R32', 'kg', 350000, 20, 1),
    ('Block máy lạnh', 'BLOCK-AC', 'cái', 3200000, 5, 1),
    ('Tụ điện', 'TU-DIEN', 'cái', 180000, 30, 1),
    ('Bo mạch', 'BO-MACH', 'cái', 1450000, 8, 1),
    ('Van nước', 'VAN-NUOC', 'cái', 220000, 20, 1),
    ('Dây điện', 'DAY-DIEN', 'm', 18000, 200, 1),
    ('CB', 'CB-DIEN', 'cái', 95000, 40, 1),
    ('Relay', 'RELAY', 'cái', 125000, 25, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    unit = VALUES(unit),
    price = VALUES(price),
    status = VALUES(status);
