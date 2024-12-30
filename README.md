# ARV
==============================

This repository contains the source-code for the GeoTales area-accounting system ARV (Arealregnskap med visualisering).


##### Arbeidslogg

<u>30.des 2024</u>
TODO;
1. rydde opp i security-groups for lambda funksjonene på AWS
2. implementere Cognito login kode i HTML frontend



X <u>28.feb 2024</u>
Todo;
Auto-analysen tar ikke med gul-til-grønn overføring fordi den fjerner allerede bebygde områder før dne beregner arealformålsendringene.
Arealformålsendringene må beregnes før allerede utbygde områder fjernes (altså før arealreserven beregnes).
Også, begynn på rapport-dashbord område

X <u>1.mars 2024</u>
Ferdig med første utkast for nytt rapport område.
Todo på rapport område; legg inn en 'fane' som viser et datatable med alle kolonner og rader i plandata-ene.
X Todo nr.2;
Endre navnet på *planlagt_utbygd_areal_m2* til *planlagt_m2*,
også, legge til *_m2* suffix på indikator areal-feltene

<u>6.mars 2024</u>
1. X Legg inn datatable fane i dashbord område.
2. V Legg inn balanseregnskap i dashbord fane
3. Q Del opp AR5 i separate tabeller for hver kommune (gjør dette ved å opprette et nytt psql schema ved navn 'ar5').
4. V Last ned (KUN FOR UTSIRA) FKB-Veg/-Tiltak/osv. dataene samt SSB-arealbruk til databasen

- Punkt under 28.feb

- Legge inn et tekst-beskrivelse felt på kulturlandskap (og alle andre relevante) overlapps-områdene



- Valg før "skriv ut pdf-rapport"
   - Legg inn innholdfortegnelse i pdf-en
   - Legg inn utvalgte områder for å fremheve
   - Kartutsnitt fra kartet inn i PDF-en som bilder


DELE OPP INDIKATORENE I TO DELER,
FØRSTE DEL ER, NATURINDIKATORENE; KVANTITATIVE INDIKATORER SOM SIER HVOR MANGE DEKAR SOM PLANLEGGES NEDBYGD
ANDRE DEL ER, UTSATTE OMRÅDER; KVALITATIVE INDIKATORER SOM KUN SIER AT DET ER PLANLAGT UTBYGGING I OMRÅDER (F.EKS. KULTURMINNE, FLOMSONE, SKREDFARESONE)

Legg til støtte for SOSI i analysen

Gjøre en face-lift på hjemmesiden, arealregnskap.no, når rapporten og analysen er i mål


-------




### TODO

- "Organization" deler alle regnskap og eksisterende filer som er lastet opp
- Last opp alle planfiler til aws, [––](https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/php_s3_code_examples.html#s3_PutObject_php_topic)

`
Versjonslogg
CO2 utregninger
En modus av kartet hvor en bruker kan se på et valgt regnskap uten å logge inn (en offentlig visningsmodus), koble dette opp til en dele-knapp

Legge en ekstra kolonne for hver avkryssede indikator. Formål: bruker kan sortere etter arealkonflikt for å identifisere områder med stor arealkonflikt.
`

* Sende epost med login-direktelenke til disse: Lene Røkke Mathisen i Haugesund kommune



Input fra Daria (Fjord kommune):
- Skille jordbruksareal etter jordkvalitet,
- Skille skogområdene på treslag og bonitet,
- Evt. synliggjøre områder uten arealkonflikt, 'frikjente områder'



- Legg inn en tooltip på hver overlapp-knapp som sier; kilde, AR5, dato, 2021 (f.eks.)
	* Viser oversikt over datering på alle kart-dataene som har inngått i kartlagene




- [Utforsk kommuneplaner](https://www.arealplaner.no/)
- [Bærum kommune planer](https://geoinnsyn3.nois.no/release/#?application=BaerumGI3)

- [Utforsk!](https://land.copernicus.eu/)

-------




### Misc.

Kjør en prosess i bakgrunnen og log ut av ssh uten at den termineres
```
$ nohup sudo -u postgres psql -d arv -f temp.sql &
```

[GeoServer-Mapbox](https://stackoverflow.com/questions/60867226/how-can-i-add-mapbox-vector-tile-layer-from-geoserver-to-mapbox)

[ogr2ogr velg input fra input-fil](https://gis.stackexchange.com/questions/172931/skip-a-specified-input-layer-in-ogr2ogr)

[Kartverket sin adresse rest-api](https://ws.geonorge.no/adresser/v1/#/)
[Kartverket sin eiendoms rest-api](https://ws.geonorge.no/eiendom/v1/#/)



https://maps.nina.no/viewer.html#planlagt-utbyggingsareal-i-norge






Roald Amundsens Vei 145, 1420 Svartskog, Norge

Hystadvegen 6, 5416 Stord, Norge

Funnsjøen 8, 7530 Meråker, Norge
