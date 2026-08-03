-- Schema: decizia de trimitere se muta de la expediere la comanda.
--
-- Decizia "trimit sau anulez" se ia **inainte** ca marfa sa plece, adica pe o
-- comanda inca neexpediata - nu pe o expediere, care exista abia dupa ce coletul
-- e deja in tranzit. De aceea:
--
--   1. `expedieri_financiar` (migrarea 022) se sterge: n-a apucat sa fie folosita
--      si nu mai are cine sa scrie in ea. Statusul "Anulat" de la expedieri ramane
--      - e o stare reala, care se poate pune si din formularul de editare.
--   2. Tabela noua `comenzi_financiar` pastreaza fotografia financiara a comenzii
--      in momentul in care operatorul apasa "Trimite": cat incaseaza, cat costa
--      marfa, cat costa carburantul pe rutele optime si ce profit iese.
--
-- Cifrele sunt o **estimare**: comanda inca n-are expedieri, deci costul de
-- carburant se calculeaza pe ruta pe care ar alege-o algoritmul pentru fiecare
-- produs, la pretul motorinei din ziua deciziei. O comanda anulata nu lasa niciun
-- rand aici.
--
-- `UNIQUE (ComandaID)` = o singura inregistrare pe comanda, deci apasarea de doua
-- ori pe "Trimite" nu duplica cifrele.

DROP TABLE IF EXISTS expedieri_financiar;

CREATE TABLE IF NOT EXISTS comenzi_financiar (
    FinanciarID       INT AUTO_INCREMENT PRIMARY KEY,
    ComandaID         INT            NOT NULL,
    incasare          DECIMAL(10, 2) NOT NULL,
    cost_marfa        DECIMAL(10, 2) NOT NULL,
    cost_carburant    DECIMAL(10, 2) NOT NULL,
    profit            DECIMAL(10, 2) NOT NULL,
    Data_inregistrare DATETIME       NOT NULL,
    UNIQUE KEY uq_financiar_comanda (ComandaID),
    CONSTRAINT fk_financiar_comanda
        FOREIGN KEY (ComandaID) REFERENCES comenzi (ComandaID)
        ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
