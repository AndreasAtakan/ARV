
# Overlapp
==============================

### Antakelser
Antakelser når prosedyrer i dette skriptet kjører:

Input tabeller
  - Planreserve finnes i tabell ved navn *\_\_planer*

Output tabell
- Overlapp områdene vil finnes i tabell ved navn *\_\_overlapp*

-------

### Hjelpe prosedyrer

- **ar5_overlapp**: Overlapp med områder fra AR5
- **omraade_overlapp**: Overlapp med annen områdetype

-------

### Kjør prosedyrer

Kall gjeldende hjelpe prosedyre med arealtype:

```sql
/* Myr */
call ar5_overlapp('myr', array[60]);


/* Skog */
call ar5_overlapp('skog', array[30]);


/* Jordbruk */
call ar5_overlapp('jordbruk', array[21, 22, 23]);


/* Åpen fastmark */
call ar5_overlapp('åpen fastmark', array[50]);


/* Skredfaresone */
call omraade_overlapp('skredfaresone', 'nve_100aar_skredfaresone');


/* Flomsone */
call omraade_overlapp('flomsone', 'nve_10aar_flomsone');


/* Områder over skoggrense */
call omraade_overlapp('over skoggrense', 'omraader_over_skoggrense');


/* Strandsone */
call omraade_overlapp('strandsone', 'ssb_strandsone');


/* Villrein områder */
call omraade_overlapp('villrein', 'villrein_omraader');


/* IBA – Important Bird Areas */
call omraade_overlapp('iba', 'iba_norge_u_svalbard');
```
