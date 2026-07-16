---
name: romania-road-network
description: Cunostinte practice despre reteaua rutiera din Romania - autostrazi, drumuri nationale, centuri ocolitoare, zone aglomerate si sezonalitate. Foloseste-l pentru a corecta estimarile "linie dreapta" ale unui API de harti cu realitatea din teren.
---

# Ce face
Un API de harti (Google Maps etc.) da distanta si timpul estimat, dar un dispecer cu experienta de condus in Romania stie unde estimarile astea sunt inselatoare. Acest skill aplica acel filtru de bun-simt peste calculul brut.

## Cunostinte de retea rutiera
- **Autostrazi principale**: A1 (Bucuresti-Pitesti-Sibiu-Deva-Arad, cu portiuni nefinalizate care obliga la deviere pe DN7/DN1), A2 (Bucuresti-Constanta, aglomerata vara), A3 (Bucuresti-Ploiesti-Comarnic-Brasov, in constructie pe portiuni), A10 (Sebes-Turda).
- **Drumuri nationale critice**: DN1 (Bucuresti-Ploiesti-Brasov, foarte aglomerat, mai ales weekend si vara spre munte), DN7 (Valea Oltului, restrictii de tonaj si viteza in zone montane), DN2 (Bucuresti-Buzau-Focsani-Bacau-Suceava).
- **Centuri ocolitoare**: centura Bucurestiului (A0/soseaua de centura) — extrem de aglomerata la orele de varf; centura Clujului, Brasovului, Timisoarei — verifica daca ruta calculata de API le foloseste sau intra inutil in oras.
- **Zone urbane cu blocaje predictibile**: Bucuresti (Calea Victoriei, Stefan cel Mare, orele 7:30-9:30 si 16:30-19:00), Cluj-Napoca (zona centrala si Manastur), Brasov (acces spre Poiana), Constanta (vara, trafic spre litoral).

## Sezonalitate si conditii speciale
- Iarna: pasuri montane (Transfagarasan, Transalpina) inchise; drumurile din zona montana (Valea Prahovei, Valea Oltului) pot avea viteza redusa sau lant obligatoriu.
- Vara: trafic intens spre litoral (DN39, A2) si spre zona montana in weekend.
- Zile de sarbatoare/vacante — trafic de tranzit mult mai mare pe rutele spre granite (Nadlac, Bors, Giurgiu).

## Cum se foloseste in practica
- Timpul dat de API pentru o ruta prin Bucuresti sau alt oras mare la ora de varf se ajusteaza in sus (marja de siguranta), nu se ia ca atare.
- Daca doua rute au timp similar pe hart, dar una trece prin centrul unui oras si alta pe centura, se prefera centura chiar daca distanta e putin mai mare.
- Restrictiile de tonaj/acces (centre istorice, zone pietonale) se verifica inainte de a trimite un curier cu vehicul mare in acea zona.

# Cand se foloseste
Ori de cate ori se construieste sau se valideaza o ruta pentru un curier — inainte de a trimite ruta finala, nu doar teoretic.
