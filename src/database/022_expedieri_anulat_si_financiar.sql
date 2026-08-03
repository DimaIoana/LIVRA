-- Schema: decizia de trimitere pentru expedierile neprofitabile.
--
-- In back office, la fiecare expediere se vede daca aduce profit sau pierdere.
-- Cand e pe pierdere, operatorul are de ales: o trimite oricum, si atunci
-- cifrele ei financiare se inregistreaza, sau o anuleaza, si atunci nu se
-- inregistreaza nimic, iar expedierea trece pe statusul "Anulat".
--
-- Doua schimbari:
--   1. `Status_expediere` primeste valoarea noua **Anulat**. Nu se atinge niciun
--      rand existent - doar lista de valori permise creste.
--   2. Tabela noua `expedieri_financiar` pastreaza fotografia financiara a
--      expedierii in momentul deciziei de trimitere: incasarea, costul marfii,
--      costul carburantului si profitul. Se salveaza abia la "Trimite", de aceea
--      e tabela separata si nu coloane in `expedieri`: o expediere anulata nu
--      lasa niciun rand aici.
--
-- `UNIQUE (ExpediereID)` = o singura inregistrare pe expediere, deci apasarea de
-- doua ori pe "Trimite" nu duplica cifrele.

ALTER TABLE expedieri
    MODIFY COLUMN Status_expediere
    ENUM('In tranzit', 'Livrat', 'Returnat', 'Intarziat', 'Anulat') NOT NULL;

CREATE TABLE IF NOT EXISTS expedieri_financiar (
    FinanciarID       INT AUTO_INCREMENT PRIMARY KEY,
    ExpediereID       INT            NOT NULL,
    Valoare_expediere DECIMAL(10, 2) NOT NULL,
    cost_marfa        DECIMAL(10, 2) NOT NULL,
    cost_carburant    DECIMAL(10, 2) NOT NULL,
    profit            DECIMAL(10, 2) NOT NULL,
    Data_inregistrare DATETIME       NOT NULL,
    UNIQUE KEY uq_financiar_expediere (ExpediereID),
    CONSTRAINT fk_financiar_expediere
        FOREIGN KEY (ExpediereID) REFERENCES expedieri (ExpediereID)
        ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
