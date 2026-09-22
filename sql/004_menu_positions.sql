-- Huawei Enterprise Shop - menu positions (top / footer-catalog / footer-info)
USE huawei_shop;

ALTER TABLE menu_items ADD COLUMN position VARCHAR(20) NOT NULL DEFAULT 'top' AFTER url;
-- существующие пункты остаются в верхнем меню
UPDATE menu_items SET position = 'top' WHERE position = '';