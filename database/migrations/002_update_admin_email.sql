UPDATE `users`
SET `email` = 'keibuildsbiztech@gmail.com',
    `password_hash` = '$2y$10$3L74riQfcvl7eML7Tk6/2.zyiNTLlxaaP1NpicVSLng69uBYs9fEu',
    `role` = 'admin',
    `status` = 'aktif'
WHERE `email` = 'admin@digilib.id'
  AND `role` = 'admin';