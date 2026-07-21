-- Comenzi plasate de clienti din magazin (src/frontend/magazin.php).
-- O comanda are un client si mai multe linii de produs.
-- Liniile pastreaza un snapshot al numelui si pretului, ca istoricul comenzii
-- sa ramana stabil chiar daca produsul din inventory se schimba ulterior.
--
-- Nota: baza avea deja o tabela `comenzi` goala (0 randuri, fara chei straine,
-- nefolosita de cod) - un schelet ramas din setup-ul initial. O inlocuim aici
-- cu structura reala. Daca in viitor ajung date acolo, NU rula acest fisier.

DROP TABLE IF EXISTS comenzi_produse;
DROP TABLE IF EXISTS comenzi;

CREATE TABLE comenzi (
    ComandaID     INT AUTO_INCREMENT PRIMARY KEY,
    ClientID      INT NOT NULL,
    Data_comanda  DATETIME NOT NULL,
    Status        ENUM('Noua', 'In procesare', 'Trimisa', 'Anulata') NOT NULL DEFAULT 'Noua',
    Total         DECIMAL(10,2) NOT NULL DEFAULT 0,
    Observatii    VARCHAR(500) NULL,
    CONSTRAINT fk_comenzi_client
        FOREIGN KEY (ClientID) REFERENCES clienti (ClientID)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE comenzi_produse (
    LinieID       INT AUTO_INCREMENT PRIMARY KEY,
    ComandaID     INT NOT NULL,
    Product_ID    VARCHAR(10) NOT NULL,
    Product_Name  VARCHAR(100) NOT NULL,
    Pret_unitar   DECIMAL(10,2) NOT NULL,
    Cantitate     INT NOT NULL,
    Subtotal      DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_linii_comanda
        FOREIGN KEY (ComandaID) REFERENCES comenzi (ComandaID)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
