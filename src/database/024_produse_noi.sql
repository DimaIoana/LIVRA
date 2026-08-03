-- Date (nu schema): 12 produse noi in magazin, PRD-0008 ... PRD-0019.
--
-- Fiecare produs primeste **trei randuri**, cate unul pe depozit (1 = Arad,
-- 2 = Braila, 3 = Pitesti), cu luni diferite - exact tiparul produselor
-- existente. De ce conteaza:
--   - magazinul arata un singur rand pe produs (cel mai recent, vezi
--     `MagazinRepository::currentFrom()`), deci lunile diferite tin catalogul
--     curat, fara duplicate;
--   - algoritmul de optimizare cauta depozitele care au produsul pe stoc
--     (`SELECT DISTINCT depozit ... AND Stock_Level > 0`), deci cu toate trei
--     depozitele orice comanda are toti candidatii de ruta.
--
-- Preturile (`Unit_Cost`) sunt asezate in scara catalogului de acum, dupa
-- migrarile 017 si 018 (cablu 21 lei, mouse 33,60, lampa 105, scaun 504,
-- monitor 1.050, laptop 2.520). Costul de achizitie (`Cost_Unitar`) e ~8-13%
-- din pret, ca la produsele existente dupa 015 si 016, si difera putin de la un
-- depozit la altul - de aceea aceeasi comanda iese cu alt cost al marfii dupa
-- depozitul de plecare.
--
-- Produsele noi n-au poza (`poze` NULL): in magazin primesc placa colorata pe
-- categorie. Se pot adauga poze oricand din back office, la Produse.
--
-- ATENTIE: nu e idempotent - rulat de doua ori adauga produsele de doua ori,
-- cu alte coduri. Se ruleaza o singura data.

INSERT INTO inventory
    (Product_ID, Product_Name, Category, Stock_Level, Reorder_Point, Monthly_Sales, Unit_Cost, Cost_Unitar, `Date`, poze, depozit)
VALUES
    -- Electronics
    ('PRD-0008', 'Mechanical Keyboard', 'Electronics', 18420, 40,  32,  420.00,  46.20, '2024-01-31', NULL, 1),
    ('PRD-0008', 'Mechanical Keyboard', 'Electronics', 24310, 40,  28,  420.00,  43.68, '2024-02-28', NULL, 2),
    ('PRD-0008', 'Mechanical Keyboard', 'Electronics', 31775, 40,  25,  420.00,  48.72, '2024-03-31', NULL, 3),

    ('PRD-0009', 'Wireless Headset',    'Electronics', 15630, 35,  27,  630.00,  69.30, '2024-01-31', NULL, 1),
    ('PRD-0009', 'Wireless Headset',    'Electronics', 22940, 35,  24,  630.00,  75.60, '2024-02-28', NULL, 2),
    ('PRD-0009', 'Wireless Headset',    'Electronics', 34180, 35,  21,  630.00,  66.15, '2024-03-31', NULL, 3),

    ('PRD-0010', 'Webcam HD',           'Electronics', 27310, 45,  38,  336.00,  33.60, '2024-01-31', NULL, 1),
    ('PRD-0010', 'Webcam HD',           'Electronics', 19875, 45,  35,  336.00,  36.96, '2024-02-28', NULL, 2),
    ('PRD-0010', 'Webcam HD',           'Electronics', 41260, 45,  30,  336.00,  30.24, '2024-03-31', NULL, 3),

    ('PRD-0011', 'Docking Station',     'Electronics', 13540, 25,  18,  840.00,  92.40, '2024-01-31', NULL, 1),
    ('PRD-0011', 'Docking Station',     'Electronics', 28110, 25,  16,  840.00,  84.00, '2024-02-28', NULL, 2),
    ('PRD-0011', 'Docking Station',     'Electronics', 36490, 25,  14,  840.00,  96.60, '2024-03-31', NULL, 3),

    ('PRD-0012', 'External SSD 1TB',    'Electronics', 21870, 30,  22,  714.00,  78.54, '2024-01-31', NULL, 1),
    ('PRD-0012', 'External SSD 1TB',    'Electronics', 16450, 30,  20,  714.00,  71.40, '2024-02-28', NULL, 2),
    ('PRD-0012', 'External SSD 1TB',    'Electronics', 39025, 30,  17,  714.00,  85.68, '2024-03-31', NULL, 3),

    ('PRD-0013', 'WiFi 6 Router',       'Electronics', 24680, 30,  26,  588.00,  64.68, '2024-01-31', NULL, 1),
    ('PRD-0013', 'WiFi 6 Router',       'Electronics', 33120, 30,  23,  588.00,  58.80, '2024-02-28', NULL, 2),
    ('PRD-0013', 'WiFi 6 Router',       'Electronics', 17940, 30,  19,  588.00,  70.56, '2024-03-31', NULL, 3),

    -- Furniture
    ('PRD-0014', 'Standing Desk',       'Furniture',   11230, 12,   9, 2100.00, 231.00, '2024-01-31', NULL, 1),
    ('PRD-0014', 'Standing Desk',       'Furniture',   26480, 12,   8, 2100.00, 210.00, '2024-02-28', NULL, 2),
    ('PRD-0014', 'Standing Desk',       'Furniture',   19760, 12,   7, 2100.00, 252.00, '2024-03-31', NULL, 3),

    ('PRD-0015', 'Filing Cabinet',      'Furniture',   14890, 18,  12,  966.00, 106.26, '2024-01-31', NULL, 1),
    ('PRD-0015', 'Filing Cabinet',      'Furniture',   30540, 18,  11,  966.00,  96.60, '2024-02-28', NULL, 2),
    ('PRD-0015', 'Filing Cabinet',      'Furniture',   22375, 18,   9,  966.00, 115.92, '2024-03-31', NULL, 3),

    ('PRD-0016', 'Monitor Stand',       'Furniture',   35720, 40,  34,  252.00,  25.20, '2024-01-31', NULL, 1),
    ('PRD-0016', 'Monitor Stand',       'Furniture',   28460, 40,  31,  252.00,  27.72, '2024-02-28', NULL, 2),
    ('PRD-0016', 'Monitor Stand',       'Furniture',   16130, 40,  28,  252.00,  22.68, '2024-03-31', NULL, 3),

    -- Accessories
    ('PRD-0017', 'Mousepad XL',         'Accessories', 44210, 90,  85,   63.00,   5.67, '2024-01-31', NULL, 1),
    ('PRD-0017', 'Mousepad XL',         'Accessories', 38970, 90,  78,   63.00,   6.30, '2024-02-28', NULL, 2),
    ('PRD-0017', 'Mousepad XL',         'Accessories', 29840, 90,  72,   63.00,   5.04, '2024-03-31', NULL, 3),

    ('PRD-0018', 'Laptop Bag',          'Accessories', 23150, 50,  44,  294.00,  29.40, '2024-01-31', NULL, 1),
    ('PRD-0018', 'Laptop Bag',          'Accessories', 31690, 50,  40,  294.00,  32.34, '2024-02-28', NULL, 2),
    ('PRD-0018', 'Laptop Bag',          'Accessories', 18720, 50,  36,  294.00,  26.46, '2024-03-31', NULL, 3),

    ('PRD-0019', 'USB Hub 4 Port',      'Accessories', 40580, 80,  68,  126.00,  12.60, '2024-01-31', NULL, 1),
    ('PRD-0019', 'USB Hub 4 Port',      'Accessories', 26310, 80,  62,  126.00,  11.34, '2024-02-28', NULL, 2),
    ('PRD-0019', 'USB Hub 4 Port',      'Accessories', 34870, 80,  57,  126.00,  13.86, '2024-03-31', NULL, 3);
