USE filipino_recipesdbs;

ALTER TABLE recipes
ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER instructions; 