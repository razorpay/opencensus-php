/* The Bank Id, Handle and Device Id will be null for Beneficiary  */
ALTER TABLE `p2p_bank_accounts` MODIFY `device_id` VARCHAR(14) CHARACTER SET utf8 COLLATE utf8_bin NULL,
                                MODIFY `handle` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NULL,
                                MODIFY `bank_id` VARCHAR(14) CHARACTER SET utf8 COLLATE utf8_bin NULL;

/*  Device Id will be null for Beneficiary
    Username has to be case insensitive, why mb4?
    https://stackoverflow.com/questions/901066/mysql-case-sensitive-search-for-utf8-bin-field */
ALTER TABLE `p2p_vpa` MODIFY `device_id` VARCHAR(14) CHARACTER SET utf8 COLLATE utf8_bin NULL,
                      MODIFY `username` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
