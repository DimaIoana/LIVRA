-- Adauga tipul de strada (drum) al fiecarei rute si il completeaza aleator.
--
-- Ordinea de dificultate / viteza, de la cea mai buna la cea mai grea (de retinut
-- pentru tot proiectul - influenteaza timpii si dificultatea de deplasare):
--   autostrada    - cea mai usoara si rapida
--   dn            - drum national: putin mai lent, dar se merge bine
--   drum judetean - mai lent si mai greu
--   drum comunal  - foarte lent si foarte greu

ALTER TABLE rute
    ADD COLUMN tip_strada ENUM('autostrada', 'dn', 'drum judetean', 'drum comunal')
        NOT NULL DEFAULT 'dn' AFTER viteza;

-- ELT alege aleator una din cele 4 valori pentru fiecare rand.
UPDATE rute SET tip_strada = ELT(FLOOR(1 + RAND() * 4),
    'autostrada', 'dn', 'drum judetean', 'drum comunal');
