USE filipino_recipesdbs;

ALTER TABLE recipes
ADD COLUMN category VARCHAR(50) DEFAULT NULL AFTER title,
ADD COLUMN dish_type VARCHAR(50) DEFAULT NULL AFTER category; 