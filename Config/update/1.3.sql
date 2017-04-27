# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- customer_family
-- ---------------------------------------------------------------------

ALTER TABLE `customer_family` ADD `is_default` TINYINT AFTER `code`;

-- ---------------------------------------------------------------------
-- customer_family_price
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `customer_family_price`
(
    `customer_family_id` INTEGER NOT NULL,
    `promo` TINYINT DEFAULT 0 NOT NULL,
    `use_equation` TINYINT DEFAULT 0 NOT NULL,
    `amount_added_before` DECIMAL(16,6) DEFAULT 0,
    `amount_added_after` DECIMAL(16,6) DEFAULT 0,
    `multiplication_coefficient` DECIMAL(16,6) DEFAULT 1,
    `is_taxed` TINYINT DEFAULT 1 NOT NULL,
    PRIMARY KEY (`customer_family_id`,`promo`),
    CONSTRAINT `fk_customer_family_id`
        FOREIGN KEY (`customer_family_id`)
        REFERENCES `customer_family` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- product_purchase_price
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `product_purchase_price`
(
    `product_sale_elements_id` INTEGER NOT NULL,
    `currency_id` INTEGER NOT NULL,
    `purchase_price` DECIMAL(16,6) DEFAULT 0,
    PRIMARY KEY (`product_sale_elements_id`,`currency_id`),
    INDEX `FI_currency_id` (`currency_id`),
    CONSTRAINT `fk_product_sale_elements_id`
        FOREIGN KEY (`product_sale_elements_id`)
        REFERENCES `product_sale_elements` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,
    CONSTRAINT `fk_currency_id`
        FOREIGN KEY (`currency_id`)
        REFERENCES `currency` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- order_product_purchase_price
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `order_product_purchase_price`
(
    `order_product_id` INTEGER NOT NULL,
    `purchase_price` DECIMAL(16,6) DEFAULT 0,
    `sale_day_equation` TEXT NOT NULL,
    PRIMARY KEY (`order_product_id`),
    CONSTRAINT `fk_order_product_id`
        FOREIGN KEY (`order_product_id`)
        REFERENCES `order_product` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- customer_family_order
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `customer_family_order`
(
    `order_id` INTEGER NOT NULL,
    `customer_family_code` VARCHAR(45) NOT NULL,
    PRIMARY KEY (`order_id`),
    INDEX `FI_customer_family_code` (`customer_family_code`),
    CONSTRAINT `fk_customer_family_order_id`
        FOREIGN KEY (`order_id`)
        REFERENCES `order` (`id`)
        ON DELETE CASCADE,
    CONSTRAINT `fk_customer_family_code`
        FOREIGN KEY (`customer_family_code`)
        REFERENCES `customer_family` (`code`)
        ON UPDATE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;