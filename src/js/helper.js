/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, September 2023                  *
*******************************************************************************/

"use strict";

function uuid(a) {
	return a ? (a^Math.random()*16>>a/4).toString(16) : ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, uuid);
}

function sumProperties(arr, props) {
	let d = {};
	for(let p of props) {
		d[p] = 0;
		for(let r of arr) { d[p] += parseFloat(r[p] || 0); }
	}
	return d;
}

function groupProperty(arr, p) {
	let d = [];
	for(let r of arr) { if(d.indexOf(r[p]) < 0) { d.push(r[p]); } }
	return d;
}

function groupArrayMany(arr, prop, vals) {
	let d = {};
	for(let r of arr) {
		let p = r[prop];
		if(!d[p]) { d[p] = {}; }
		for(let v of vals) { d[p][v] = Math.round((d[p][v] || 0) + r[v]); }
	}
	return d;
}

function groupArray(arr, prop, val) {
	let d = groupArrayMany(arr, prop, [val]);
	for(let p in d) { d[p] = d[p][val]; }
	return d;
}

function formatDecimal(n, d) {
	return +n.toFixed(d);

	/* Alternative implementation
	let c = Math.pow(10, d);
	return Math.round((n + Number.EPSILON) * c) / c;
	*/
}



function mapRegisterControlPosition(map, name) {
	if(map._controlPositions[ name ]) { return; }
	let cont = document.createElement("div");
	cont.className = `mapboxgl-ctrl-${name}`;
	map._controlContainer.appendChild(cont);
	map._controlPositions[ name ] = cont;
}



function polygonsOverlapp(fc1, fc2) {
	let overlaps = [];

	fc1.features.forEach(f1 => {
		if(f1.geometry.type !== "Polygon" && f1.geometry.type !== "MultiPolygon") { return; }
		fc2.features.forEach(f2 => {
			if(f2.geometry.type !== "Polygon" && f2.geometry.type !== "MultiPolygon") { return; }
			let intersection = turf.intersect(f1, f2);
			if(intersection) {
				intersection.properties = { ...f1.properties, ...f2.properties };
				overlaps.push(intersection);
			}
		});
	});

	return overlaps;
}




const _FELT = {
	"id":						"ID",
	"objtype":					"Objekttype",
	"planidentifikasjon":		"Planidentifikasjon",
	"planlegging_status":		"Planleggingsstatus",
	"plankilde":				"Plankilde",
	"planreservedato":			"Planreservedato",
	"lokalid":					"Lokal-ID",
	"versjonid":				"Versjon-ID",
	"eierform":					"Eierform",
	"område":					"Område",
	"beskrivelse":				"Beskrivelse",
	"arealformaal":				"Arealformål",
	"arealformaalsbeskrivelse":	"Arealformålsbeskrivelse",
	"arealformaalsgruppe":		"Arealformålsgruppe",
	"arealklasse":				"Arealklasse",
	"arealstatus":				"Arealstatus",
	"ikraft_dato":				"Ikrafttredelsesdato",
	"plannavn":					"Plannavn",
	"plantype":					"Plantype",
	"planstatus":				"Planstatus",
	"planbestemmelse":			"Planbestemmelse",
	"planalder":				"Planalder",
	"lovreferanse":				"Lovreferanse",
	"kommunenummer":			"Kommunenummer",
	"kommune":					"Kommune",
	"fylkesnummer":				"Fylkesnummer",
	"fylke":					"Fylke",
	"planlagt_m2":				"Planlagt utbygd m<sup>2</sup>",
	"myr_m2":					"Myr m<sup>2</sup>",
	"skog_m2":					"Skog m<sup>2</sup>",
	"jordbruk_m2":				"Jordbruk m<sup>2</sup>",
	"aapen_fastmark_m2":		"Åpen fastmark m<sup>2</sup>",
	//"bebgyd_m2":				"Urbane områder m<sup>2</sup>",
	"kulturlandskap_m2":		"Kulturlandskap m<sup>2</sup>",
	"skredfaresone_m2":			"Skredfaresone m<sup>2</sup>",
	"flomsone_m2":				"Flomsone m<sup>2</sup>",
	"over_skoggrense_m2":		"Over-skoggrense m<sup>2</sup>",
	"strandsone_m2":			"Strandsone m<sup>2</sup>",
	"villrein_m2":				"Villrein m<sup>2</sup>",
	"iba_m2":					"Viktige-fugleområder m<sup>2</sup>",
	"utslippseffekt_5aar":		"Utslippseffekt 5 år tonn",
	"utslippseffekt_20aar":		"Utslippseffekt 20 år tonn",
	"utslippseffekt_75aar":		"Utslippseffekt 75 år tonn",

	"areal_m2":					"Areal m<sup>2</sup>"
};


const ar5_arealtype = {
		21: "Fulldyrka jord",
		22: "Overflatedyrka jord",
		23: "Innmarksbeite",
		30: "Skog",
		50: "Åpen fastmark",
		60: "Myr",
		81: "Ferskvann",
		82: "Hav",
		70: "Bre",
		11: "Bebygd",
		12: "Samferdsel",
		99: "Ikke kartlagt"
	},
	ar5_treslag = {
		31: "Barskog",
		32: "Lauvskog",
		33: "Blandingsskog",
		39: "Ikke tresatt",
		98: "Ikke relevant",
		99: "Ikke registrert"
	},
	ar5_skogbonitet = {
		15: "Særs høg",
		14: "Høg",
		13: "Middels",
		12: "Lav",
		11: "Impediment",
		98: "Ikke relevant",
		99: "Ikke registrert"
	},
	ar5_grunnforhold = {
		46: "Konstruert",
		45: "Organiske jordlag",
		44: "Jorddekt",
		43: "Grunnlendt",
		42: "Fjell i dagen",
		41: "Blokkmark",
		98: "Ikke relevant",
		99: "Ikke registrert"
	},
	ar5_jordbruk = {
		21: "Fulldyrka jord",
		22: "Overflatedyrka jord",
		23: "Innmarksbeite"
	};
const planreserve_reguleringsnivå = {
		1: "Kommuneplan",
		2: "Annen reguleringsplan",
		3: "Områderegulering",
		4: "Detaljregulering"
	},
	planreserve_plantype = {
		4: "Statlig arealplan",
		10: "Fylkesplanens arealdel",
		11: "Fylkesdelplan",
		12: "Regionalplan",
		20: "Kommuneplanens arealdel",
		21: "Kommunedelplan",
		22: "Mindre endring av kommune(del)plan",
		30: "Eldre reguleringsplan",
		31: "Mindre reguleringsendring",
		32: "Bebyggelsesplan ihht. Reguleringsplan",
		33: "Bebyggelsesplan ihht kommunepl. arealdel",
		34: "Områderegulering",
		35: "Detaljregulering"
	},
	planreserve_planstatus = {
		1: "Planlegging igangsatt",
		2: "Planforslag",
		3: "Endelig vedtatt arealplan",
		4: "Opphevet",
		5: "Utgått/erstattet",
		6: "Vedtatt plan med utsatt rettsvirkning",
		8: "Overstyrt"
	},
	planreserve_planbestemmelse = {
		1: "Med bestemmelser som egen tekst",
		2: "Uten bestemmelser",
		3: "Planbestemmelser fremgår kun av kartet",
		4: "Planbestemmelser både kart og tekst"
	},
	planreserve_lovreferanse = {
		1: "Før BL 1924",
		2: "BL 1924",
		3: "BL 1965",
		4: "PBL 1985",
		5: "PBL 1985 eller før",
		6: "PBL 2008"
	},
	planreserve_arealstatus = {
		1: "Nåværende",
		2: "Framtidig",
		3: "Videreutvikling av nåværende"
	},
	planreserve_eierform = {
		1: "Offentlig formål",
		2: "Felles",
		3: "Annen eierform"
	},
	planreserve_planforholdstype = {
		1: "endrer / blir endret av",
		2: "overstyrer / overstyres av",
		3: "arver bestemmelser fra / overfører bestemmelser til",
		4: "erstatter / blir erstattet av",
		5: "erstatter delvis / blir delvis erstattet av"
	},
	planreserve_planbehandlingtype = {
		10: "planforslag tatt til behandling",
		11: "besluttet offentlig ettersyn",
		12: "mottatt varsel om innsigelse",
		13: "plan vedtatt med innsigelse",
		14: "plan endelig vedtatt",
		15: "ikraftsatt",
		16: "kunngjøring endelig vedtatt plan",
		17: "mottatt klage",
		18: "vedtatt opphevelse",
		19: "registrert kommentar",
		20: "vedtatt mindre endring"
	},
	planreserve_plandokumentype = {
		"PLANKART": "arealplankart",
		"PLANBEST": "bestemmelser",
		"VEDTAK": "vedtak",
		"PLANBESKR": "planbeskrivelse",
		"JURILLUST": "juridisk bindende illustrasjon",
		"ILLUST": "illustrasjon",
		"KONSUTR": "konsekvensutredning",
		"RAPPORT": "rapport",
		"INNSIGELSE": "innsigelse",
		"KLAGE": "klage",
		"KUNNGJ": "kunngjøring",
		"TEGNFKL": "tegnforklaring",
		"GEOREFPLAN": "georeferert plankart"
	};
const plan_vertikalnivå = {
	1: "Under grunnen (tunnel)",
	2: "På grunnen/vannoverflate",
	3: "Over grunnen (bru)",
	4: "På bunnen (vann/sjø)",
	5: "I vannsøylen"
};
const plan_rolletype = {
	1: "forslagstiller",
	2: "utarbeidet av"
};



const _felt_matrikkel = {
	"teig_id": "Teig ID",
	"overlapp_type": "Arealtype",
	"areal_m2": "Areal (m<sup>2</sup>)"
};
const _FELT_MATRIKKEL = {
	"utbyggingspotensiale": {
		..._felt_matrikkel,
		"planreserve_arealformaal": "Offentlig planformål",
		"planreserve_reguleringsnivå": "Reguleringsnivå",
		"planreserve_ikrafttredelsesdato": "Ikrafttredelsesdato"
	},
	"aapen_fastmark": {
		..._felt_matrikkel,
		"ar5_grunnforhold": "Grunnforhold"
	},
	"myr": _felt_matrikkel,
	"skog": {
		..._felt_matrikkel,
		"ar5_treslag": "Treslag",
		"ar5_skogbonitet": "Skogbonitet",
		"ar5_grunnforhold": "Grunnforhold"
	},
	"jordbruk": {
		..._felt_matrikkel,
		"ar5_jordbruk": "Jordbrukstype"
	},
	"kulturlandskap": _felt_matrikkel,
	"strandsone": _felt_matrikkel,
	"skredfaresone": _felt_matrikkel,
	"flomsone": _felt_matrikkel,
	"villrein": _felt_matrikkel
};

const _MATRIKKEL_COLORS = {
	"utbyggingspotensiale_m2": "#E6E600",
	"aapen_fastmark_m2": "#A3A375",
	"myr_m2": "#7575A3",
	"skog_m2": "#669900",
	"jordbruk_m2": "#CC9900",
	"kulturlandskap_m2": "#CC6600",
	"strandsone_m2": "#CC3300",
	"skredfaresone_m2": "#E60000",
	"flomsone_m2": "#0066CC",
	"villrein_m2": "#BF4080",
};

const _FELT_TEIG_AREAL = {
	"utbyggingspotensiale_m2": "Utbyggingspotensiale",
	"aapen_fastmark_m2": "Åpen fastmark",
	"myr_m2": "Myr",
	"skog_m2": "Skog",
	"jordbruk_m2": "Jordbruk",
	"kulturlandskap_m2": "Kulturlandskap",
	"strandsone_m2": "Strandsone",
	"skredfaresone_m2": "Skredfaresone",
	"flomsone_m2": "Flomsone",
	"villrein_m2": "Villreinområder"
};
const _FELT_TEIG_AREAL_sub = {
	"utbyggingspotensiale_m2": {
		"utbyggingspotensiale_kommuneplan_m2": "Fra kommuneplan",
		"utbyggingspotensiale_annen_reguleringsplan_m2": "Fra annen reguleringsplan",
		"utbyggingspotensiale_områderegulering_m2": "Fra områderegulering",
		"utbyggingspotensiale_detaljregulering_m2": "Fra detaljregulering"
	},
	"skog_m2": {
		"skog_særs_høy_m2": "Særs høy bonitet",
		"skog_høy_m2": "Høy bonitet",
		"skog_middels_m2": "Middels bonitet",
		"skog_lav_m2": "Lav bonitet",
		"skog_impediment_m2": "Impediment"
	},
	"jordbruk_m2": {
		"jordbruk_fulldyrket_m2": "Fulldyrket mark",
		"jordbruk_overflatedyrket_m2": "Overflatedyrket mark",
		"jordbruk_innmarksbeite_m2": "Innmarksbeite"
	}
};
const _FELT_TEIG = {
	"objid": "Objekt-ID",
	"teigid": "Teig-ID",
	"matrikkelenhetid": "Matrikkelenhet-ID",
	"objtype": "Objekttype",
	"matrikkelenhetstype": "Matrikkelenhetstype",
	"matrikkelnummertekst": "Matrikkelnummer tekst",
	"noyaktighetsklasseteig": "Nøyaktighetsklasse",

	//"datafangstdato": "Data-fangstdato",
	"datauttaksdato": "Data-uttaksdato",
	"oppdateringsdato": "Oppdateringsdato",
	"gardsnummer": "Gårdsnummer",
	"bruksnummer": "Bruksnummer",
	"bruksnavn": "Bruksnavn",
	"kommunenummer": "Kommunenummer",
	"kommunenavn": "Kommunenavn",

	"punktfeste": "Punktfeste",
	"avklartandeler": "Har avklart andeler?",
	"avklarteiere": "Har avklart eiere?",
	"haravtalegrensepunktfeste": "Har avtale grensepunktfeste?",
	"haravtalestedbundenrettighet": "Har avtale stedbunden rettighet?",
	"hargrunnforurensing": "Har grunnforurensing?",
	"harkulturminne": "Har kulturminne?",
	"harregistrertgrunnerverv": "Har registrert grunnerverv?",
	"harregistrertjordskiftekrevd": "Har registrert jordskifte krevd?",
	"teigmedflerematrikkelenheter": "Har teig flere matrikkelenheter?",
	"tvist": "Har tvist?",
	"uregistrertjordsameie": "Har uregistrert jordsameie?"
	//, ..._FELT_TEIG_AREAL
};
const _FELT_TEIG_SEARCH = {
	"representasjonspunkt": { text: "Punkt", default: false },

	"objid": { text: "Objekt-ID", default: false },
	"teigid": { text: "Teig-ID", default: true },
	"matrikkelenhetid": { text: "Matrikkelenhet-ID", default: false },
	"matrikkelenhetstype": { text: "Matrikkelenhetstype", default: false },
	"matrikkelnummertekst": { text: "Matrikkelnummer", default: true },
	"noyaktighetsklasseteig": { text: "Nøyaktighetsklasse", default: false },

	"gardsnummer": { text: "Gårdsnummer", default: false },
	"bruksnummer": { text: "Bruksnummer", default: false },
	"bruksnavn": { text: "Bruksnavn", default: true },
	"kommunenummer": { text: "Kommunenummer", default: false },
	"kommunenavn": { text: "Kommunenavn", default: true },

	"areal_m2": { text: "Størrelse (daa)", default: true },
	"utbyggingspotensiale_m2": { text: "Utbyggingspotensiale (daa)", default: false },
	"aapen_fastmark_m2": { text: "Åpen fastmark (daa)", default: false },
	"myr_m2": { text: "Myr (daa)", default: false },
	"skog_m2": { text: "Skog (daa)", default: false },
	"jordbruk_m2": { text: "Jordbruk (daa)", default: false },
	"kulturlandskap_m2": { text: "Kulturlandskap (daa)", default: false },
	"strandsone_m2": { text: "Strandsone (daa)", default: false },
	"skredfaresone_m2": { text: "Skredfaresone (daa)", default: false },
	"flomsone_m2": { text: "Flomsone (daa)", default: false },
	"villrein_m2": { text: "Villreinområder (daa)", default: false }
};



const _AREALFORMAALSGRUPPER = {
	"01 Bolig eller sentrumsformål":	[ "100 Byggeområde","101 Bybebyggelse","102 Tettbebyggelse","110 Boligområde","120 Senterområde","400 Område som er eller skal båndlegges","1000 Bebyggelse og anlegg - generalisert (utgått)","1001 Bebyggelse og anlegg","1110 Boligbebyggelse","1130 Sentrumsformål","100 Byggeområde","110 Boliger","111 Frittliggende småhusbebyggelse","112 Konsentrert småhusbebyggelse","113 Blokkbebyggelse","115 Garasje","760 Felles gårdsplass","800 Fornyelsesområde","1000 Bebyggelse og anlegg - generalisert (utgått)","1001 Bebyggelse og anlegg","1110 Boligbebyggelse","1111 Boligbebyggelse-frittliggende småhusbebyggelse","1112 Boligbebyggelse-konsentrertsmåhusbebyggelse","1113 Boligbebyggelse-blokkbebyggelse","1130 Sentrumsformål" ],
	"02 Fritidsbebyggelse":				[ "140 Fritidsbebyggelse","1120 Fritidsbebyggelse","150 Fritidsbebyggelse","1120 Fritidsbebyggelse","1121 Fritidsbebyggelse-frittliggende","1122 Fritidsbebyggelse-konsentrert","1123 Fritidsbebyggelse-blokk","1124 Kolonihage" ],
	"03 Tjenesteyting":					[ "150 Offentlig bygning","155 Bygn. med særskilt allmennyttig formål","160 Kommunalteknisk anlegg","162 Avfallsbehandling","163 Kommunalteknisk virksomhet","1160 Offentlig eller privat tjenesteyting","160 Offentlig bebyggelse","162 Offentlig bygg - barnehage","163 Offentlig bygg - undervisning","164 Offentlig bygg - institusjon","166 Offentlig bygg - kirke","960 Offentlig/Allmennyttig","1160 Offentlig eller privat tjenesteyting","1161 Barnehage","1162 Undervisning","1163 Institusjon (utgått)","1164 Forsamlingslokale for religionsutøvelse","1165 Forsamlingslokale (utgått)","1166 Administrasjon","167 Offentlig bygg - forsamlingslokale","169 Offentlig bygg - administrativt bygg","170 Allmennyttig formål","172 Allmennyttig formål-barnehage","173 Allmennyttig formål-undervisning","174 Allmennyttig formål-institusjon","176 Allmennyttig formål-kirke","177 Allmennyttig formål-forsamlingslokale","179 Allmennyttig formål-administrativt bygg","1167 Kulturinstitusjon","1168 Helse-/omsorgsinstitusjon","1169 Annen offentlig eller privat tjenesteyting" ],
	"04 Handel":						[ "131 Forretning","1140 Kjøpesenter","1150 Forretninger","120 Forretning","1140 Kjøpesenter","1150 Forretninger" ],
	"05 Turistformål":					[ "1170 Fritids- og turistformål","180 Herberge og bevertningssted","181 Hotell","182 Bevertning","692 Fornøyelsespark","1170 Fritids- og turistformål","1171 Utleiehytter","1172 Fornøyelsespark eller temapark","1173 Campingplass","1174 Leirplass","612 Campingplass" ],
	"06 Næringsvirksomhet":				[ "130 Erverv","132 Kontor","133 Industri","134 Lager","1300 Næringsvirksomhet","130 Kontor","140 Industri","192 Bensinstasjon","685 Handelsgartneri","920 Forretning/Kontor","921 Forretning/Kontor/Industri","922 Forretning/Industri","923 Forretning/Kontor/Offentlig","924 Forretning/Offentlig","930 Kontor/Industri","931 Kontor/Offentlig","939 Kontor/Bensinstasjon","980 Bevertning/Bensinstasjon","1300 Næringsbebyggelse","1310 Kontor","1320 Hotell/overnatting","1330 Bevertning","1340 Industri","1350 Lager","1360 Bensinstasjon/vegserviceanlegg","1390 Annen næring","1810 Forretning/kontor","1811 Forretning/kontor/industri","1812 Forretning/industri","1813 Forretning/kontor/tjenesteyting","1814 Forretning/tjenesteyting","1824 Næring/tjenesteyting","1825 Kontor/lager","1826 Industri/lager","1830 Kontor/industri","1831 Kontor/tjenesteyting" ],
	"07 Råstoffutvinning":				[ "300 Område for råstoffutvinning","310 Gruvedrift","320 Masseuttak","1200 Råstoffutvinning","670 Steinbrudd og masseuttak","671 Vesentlige terrenginngrep","1200 Råstoffutvinning","1201 Steinbrudd og masseuttak" ],
	"08 Kombinerte formål":				[ "1800 Kombinert bebyggelse og anleggsformål","900 Kombinert formål","910 Bolig/Forretning","911 Bolig/Forretning/Kontor","912 Bolig/Kontor","913 Bolig/Offentlig","990 Annet kombinert formål","1800 Kombinert bebyggelse og anleggsformål","1801 Bolig/forretning","1802 Bolig/forretning/kontor","1803 Bolig/tjenesteyting","1804 Bolig/kontor","1820 Næring/kontor (utgått)","1821 Næring/kontor/industri (utgått)","1822 Næring/industri (utgått)","1823 Næring/kontor/tjenesteyting (utgått)","1900 Angitt bebyggelse og anleggsformål kombinert med andre angitte hovedformål" ],
	"09 Idrettsanlegg":					[ "171 Idrettsanlegg","1400 Idrettsanlegg","440 Anlegg for idrett og sport","1400 Idrettsanlegg","1410 Skianlegg","1420 Skiløypetrasé","1430 Idrettsstadion","1440 Nærmiljøanlegg","1450 Golfbane","1460 Motorsportanlegg","1470 Skytebane","1490 Andre idrettsanlegg","520 Skytebane","615 Idrettsanl., ikke offentlig tilgjengelig","616 Golfbane" ],
	"10 Andre formål":					[ "161 Grav- og urnelund","190 Annet byggeområde","191 Garasjeanlegg (regform)","470 Område som skal reguleres etter pbl. 1985","1500 Andre typer bebyggelse og anlegg","1500 Andre typer nærmere angitt bebyggelse og anlegg","1600 Uteoppholdsareal","199 Annet byggeområde","240 Parsellhage","382 Småbåtanlegg (landdelen)","530 Ildsfarlig opplag ol","690 Særskilt anlegg","693 Vindkraft","700 Fellesområde","750 Felles lekeareal","790 Annet fellesareal","991 Formålsrekkefølge","1500 Andre typer bebyggelse og anlegg","1501 Godsterminal","1502 Godslager","1510 Energianlegg","1520 Fjernvarmeanlegg","1530 Vindkraftanlegg","1540 Vann- og avløpanlegg","1541 Vannforsyningsanlegg","1542 Avløpsanlegg","1700 Grav- og urnelund","617 Grav- og urnelund","620 Anlegg i grunnen","621 Kommunalteknisk virksomhet","623 Vann- og avløpsanlegg","627 Anlegg for radionavigasjon","630 Fiskebruk","1550 Renovasjonsanlegg","1560 Øvrige kommunaltekniske anlegg","1570 Telekommunikasjonsanlegg","1589 Uthus/naust/badehus","1590 Annen særskilt angitt bebyggelse og anlegg","1600 Uteoppholdsareal","1610 Lekeplass","1620 Gårdsplass","1630 Parsellhage","1690 Annet uteoppholdsareal","1700 Grav-og urnelund","1710 Krematorium","1730 Nødvendige bygg og anlegg for grav- og urnelund" ],
	"11 Samferdselsanlegg":				[ "2080 Parkeringsanlegg", "600 Viktige ledd i kommunikasjonssystemet","610 Veiareal","620 Parkering","630 Fotgjengerstrøk","631 Bilfritt trafikkareal","632 Gang- og sykkelareal","2020 Bane (nærmere angitte baneformål)","2030 Lufthavn","2050 Hovednett for sykkel","2060 Kollektivnett","2070 Kollektivknutepunkt","2080 Parkering","2100 Trase for teknisk infrastruktur","2800 Kombinerte formål samf., infrastr.","2800 Kombinerte formål for samferdselsanlegg og/eller teknisk infrastrukturtraseer","190 Garasjeanlegg og bensinstasjon","191 Garasjeanlegg","300 Offentlig trafikkområde","310 Kjørevei","311 Gate med fortau","319 Annen veigrunn","320 Gang-/sykkelvei","321 Sykkelvei","322 Gangvei","330 Gatetun","691 Taubane","710 Felles avkjørsel","720 Felles gangareal","730 Felles parkeringsplass","770 Felles garasjeanlegg","992 Midlertidig trafikkområde","1119 Garasjeanlegg for bolig-/fritidsbebyggelse","2000 Samferdselsanlegg og teknisk infrastruktur(arealer) - generalisert (utgått)","2001 Samferdselsanlegg og teknisk infrastruktur(arealer)","2010 Veg","2011 Kjøreveg","2012 Fortau","2013 Torg","2014 Gatetun","2015 Gang-/sykkelveg","2016 Gangveg/gangareal/gågate","2017 Sykkelanlegg","2018 Annen veggrunn - tekniske anlegg","2019 Annen veggrunn - grøntareal","2020 Bane (nærmere angitt baneformål)","2021 Trasé for jernbane","2022 Trasé for sporveg/forstadsbane","2023 Trasé for taubane","2024 Stasjons-/terminalbygg","640 Terminal","641 Terminalbygg","650 Jernbaneareal","660 Sporveisareal","670 Flyplass","2000 Samferdselsanlegg og teknisk infrastruktur - generalisert (utgått)","2001 Samferdselsanlegg og teknisk infrastruktur (arealer)","2010 Veg","331 Torg","332 Rasteplass","333 Parkeringsplass","340 Kollektivanlegg","341 Bussterminal","342 Bussholdeplass","343 Drosjeholdeplass","350 Jernbane","360 Sporvei/forstadsbane","370 Flyplass med administrasjonsbygg","371 Flyplass/taxebane","372 Hangarer, verksteder, admin.bygg","399 Annet trafikkområde (på land)","500 Fareområde","510 Høyspenningsanlegg","601 Privat vei","624 Fjernvarmeanlegg","625 Anlegg for telekommunikasjon","640 Frisiktsone","641 Restriksjonsområde flyplass","2025 Holdeplass/plattform","2026 Leskur/plattformtak","2027 Tekniske bygg/konstruksjoner","2028 Annen banegrunn - tekniske anlegg","2029 Annen banegrunn - grøntareal","2030 Lufthavn generelt","2031 Lufthavn - landings-/taxebane","2032 Lufthavn - terminalbygg","2033 Lufthavn- hangarer/administrasjonsbygg","2034 Landingsplass for helikopter o.a.","2045 Navigasjonsinstallasjon","2050 Hovednett for sykkel","2060 Kollektivnett","2061 Trase for nærmere angitt kollektivtransport","2070 Kollektivknutepunkt","2071 Kollektivanlegg","2072 Kollektivterminal","2073 Kollektivholdeplass","2074 Pendler-/innfartsparkering","2081 Rasteplass","2082 Parkeringsplasser","2083 Parkeringshus/-anlegg","2100 Trase for teknisk infrastruktur (utgått)","2101 Teknisk infrastruktur","2110 Energinett","2120 Fjernvarmenett","2140 Vann- og avløpsnett","2141 Vannforsyningsnett","2142 Avløpsnett","2143 Overvannsnett","2150 Avfallssug","2160 Telekommunikasjonsnett","2170 Sikringsanlegg","2180 Andre tekniske infrastrukturtraseer","2190 Kombinerte tekniske infrastrukturtraseer","2900 Angitte samferdselsanlegg og/eller teknisk infrastrukturtraseer kombinert " ],
	"12 Blå/grønnstruktur":				[ "170 Friområde","172 Park/turvei","173 Skiløype","3000 Grønnstruktur - generalisert (utgått)","3001 Grønnstruktur (utgått)","3002 Blå/grønnstruktur","3020 Naturområde - grønnstruktur","3030 Turdrag","3040 Friområde","3050 Park","3100 Overvannstiltak","3800 Kombinerte grøntstrukturformål","400 Offentlig friområde","410 Park","420 Turvei","421 Skiløype","430 Anlegg for lek","450 Leirplass","459 Annet friområde","611 Parkbelte i industriområde","613 Friluftsområde (på land)","650 Naturvernområde på land","652 Klimavernsone","663 Bevaring av landskap og vegetasjon","780 Felles grøntareal","3000 Grønnstruktur - generalisert (utgått)","3001 Grønnstruktur (utgått)","3002 Blå/grønnstruktur","3020 Naturområde - grønnstruktur","3030 Turdrag","3031 Turveg","3040 Friområde","3041 Badeplass/-område","3050 Park","3060 Vegetasjonsskjerm","3061 Vannspeil","3100 Overvannstiltak","3110 Infiltrasjon/fordrøyning/avledning","3800 Kombinerte grøntstrukturformål","3900 Angitt grøntstruktur kombinert med andreangitte hovedformål","5120 Naturformål av LNFR","5130 Friluftsformål","5300 Naturvern","5500 Særlige landskapshensyn","5600 Vern av kulturmiljø eller kulturminne" ],
	"13 Forsvaret":						[ "460 Båndlegges for forsvaret","626 Anlegg for forsvaret","4000 Forsvaret - generalisert (utgått)","4001 Forsvaret","4010 Ulike typer militære formål","4020 Skytefelt/øvingsområde","4030 Forlegning/leir","4800 Kombinerte militærformål","4000 Forsvaret - generalisert (utgått)","4001 Forsvaret","4010 Ulike typer militære formål","4020 Skytefelt/øvingsområde","4030 Forlegning/leir","4800 Kombinerte militærformål","4900 Angitt militært formal kombinert med andreangitte hovedformål" ],
	"14 LNFR":							[ "200 Landbruks-, Natur- og Friluftsområde","210 LNF-område","330 Myrarealer","410 Båndlegging etter lov om naturvern","420 Båndlegging etter lov om kulturminner","490 Båndlegging etter annet lovverk","499 Båndlegging etter flere lovverk","5000 Landbruk-, natur- og friluftsformål samt reindrift (LNFR) - generalisert (utgått)","5001 Landbruk-, natur- og friluftsformål samt reindrift (LNFR) (utgått)","5100 LNRF areal for nødvendige tiltak for landbruk og reindrift","200 Landbruksområde","210 Jord- og skogbruk","220 Landbruksområde reindrift","230 Gartneri","299 Annet landbruksområde","540 Rasfare","550 Flomfare","590 Særskilt angitt fare","645 Grunnvannsmagasin","646 Nedslagsfelt for drikkevann","680 Spesialområde reindrift","681 Pelsdyranlegg","5000 Landbruks-, natur- og friluftsformål samt reindrift (LNFR) - generalisert (utgått)","5001 Landbruks-, natur- og friluftsformål samt reindrift (LNFR) (utgått)","5100 LNFR areal for nødvendige tiltak for landbruk og reindrift","5110 Landbruksformål","5111 Jordbruk","5112 Skogbruk","5113 Seterområde","5114 Gartneri","5115 Pelsdyranlegg","5140 Reindriftformål","5400 Jordvern","5900 LNFR formål kombinert med andre angitte hovedformål" ],
	"15 LNFR spredt":					[ "220 LNF-område m/spredt bebyggelse","221 LNF-område m/spredt boligbebyggelse","222 LNF-område m/spredt ervervsbebyggelse","223 LNF-område m/spredt fritidsbebyggelse","5200 LNFR areal for spredt bolig- fritids- eller næringsbebyggelse, mv","5210 Spredt boligbebyggelse","5220 Spredt fritidsbebyggelse","5230 Spredt næringsbebyggelse","5200 LNFR areal for spredt bolig- fritids- ellernæringsbebyggelse, mv","5210 Spredt boligbebyggelse","5220 Spredt  fritidsbebyggelse","5230 Spredt  næringsbebyggelse" ],
	"16 Havner og småbåthavner":		[ "521 Småbåthavn","680 Havn","2040 Havn","2044 Molo","6110 Ankringsområde","6120 Opplagsområde","6130 Riggområde","6220 Havneområde i sjø","380 Havneområde (landdelen)","381 Kai","392 Havneområde i sjø","462 Småbåthavn","618 Privat småbåtanlegg (landdelen)","619 Privat småbåthavn (sjødelen)","1587 Småbåtanlegg i sjø og vassdrag","1588 Småbåtanlegg i sjø og vassdrag med tilhørendestrandsone","2040 Havn","2041 Kai","2042 Havneterminaler","2043 Havnelager","2044 Molo","6230 Småbåthavn","6220 Havneområde i sjø","6230 Småbåthavn","6240 Bøyehavn","6730 Småbåtanlegg i sjø og vassdrag (utgått)","6740 Småbåtanlegg i sjø og vassdrag med tilhørende strandsone (utgått)","6750 Uthus/naust/badehus (utgått)" ],
	"17 Sjø og vassdrag, flerbruk":		[ "500 Område for vern av sjø og vassdrag","510 Vann med restriksjon (drikkevannskilde)","520 Vannareal for allment friluftsliv","522 Idrettsområde","530 Vannareal for allmenn flerbruk","531 Ferdselsområde","532 Fiskeområde","6000 Bruk og vern av sjø og vassdrag med tilhørende strandsone - generalisert (utgått)","6001 Bruk og vern av sjø og vassdrag med tilhørende strandsone","6100 Ferdsel","6200 Farleder","390 Trafikkområde i sjø og vassdrag","391 Skipsled","398 Annet trafikkområde i sjø/vassdrag","460 Friområde i sjø og vassdrag","461 Badeområde","463 Regattabane","469 Annet friområde i sjø/vassdrag","614 Friluftsområde i sjø/vassdrag","622 Drikkevannsmagasin","651 Naturvernområde i sjø/vassdrag","6000 Bruk og vern av sjø og vassdrag med tilhørende strandsone - generalisert (utgått)","6001 Bruk og vern av sjø og vassdrag med tilhørende strandsone","6100 Ferdsel","6110 Ankringsomåde","6120 Opplagsomåde","6130 Riggområde","6200 Farled","6210 Skipsled (utgått)","6211 Hoved-/biled","6300 Fiske","6310 Fiskeområde","6320 Låssettingsplasser","6330 Oppvekstområde for yngel","540 LNF-område i sjø og vassdrag","541 Friluftsområde i sjø og vassdrag","542 Naturområde i sjø og vassdrag","590 Annen særskilt bruk eller vern","6300 Fiske","6500 Drikkevann","6600 Naturområde","6700 Friluftsområde","6800 Kombinerte formål i sjø og vassdrag med eller uten tilhørende strandsone","6500 Drikkevann","6600 Naturområde","6610 Naturområde i sjø og vassdrag","6620 Naturområde i sjø og vassdrag med tilhørende strandsone","6700 Friluftsområde","6710 Friluftsområde i sjø og vassdrag","6720 Friluftsområde i sjø og vassdrag med tilhørende strandsone","6760 Idrett/vannsport","6770 Badeområde","6800 Kombinerte formål i sjø og vassdrag ","6900 Angitt formål i sjø og vassdrag " ],
	"18 Akvakultur":					[ "533 Akvakulturområde","6400 Akvakultur","631 Andre anlegg i vassdrag / sjø","6400 Akvakultur","6410 Akvakulturanlegg i sjø og vassdrag","6420 Akvakulturanlegg i sjø og vassdrag med tilhørende strandsone","6430 Fangstbasert levendelagring" ],
	"19 Udefinert":						[ "600 Spesialområde","660 Bevaringsområde","661 Bevaring av bygninger","662 Bevaring av anlegg","699 Annet spesialområde","999 Unyansert formål" ]
};


const _PLAN_COLORS = {
		"arealformaalsgruppe": {
			"01 Bolig eller sentrumsformål":	"#E6E600",
			"02 Fritidsbebyggelse":				"#FFCC33",
			"03 Tjenesteyting":					"#FF6699",
			"04 Handel":						"#9999FF",
			"05 Turistformål":					"#666699",
			"06 Næringsvirksomhet":				"#9966CC",
			"07 Råstoffutvinning":				"#AC6668",
			"08 Kombinerte formål":				"#996600",
			"09 Idrettsanlegg":					"#669900",
			"10 Andre formål":					"#CCFFFF",
			"11 Samferdselsanlegg":				"#999999",
			"12 Blå/grønnstruktur":				"#00CC99",
			"13 Forsvaret":						"#999966",
			"14 LNFR":							"#CC6600",
			"15 LNFR spredt":					"#B35900",
			"16 Havner og småbåthavner":		"#66B1B1",
			"17 Sjø og vassdrag, flerbruk":		"#0066CC",
			"18 Akvakultur":					"#3333CC",
			"19 Udefinert":						"#4D4D4D"
		},
		"arealklasse": {
			"01 Bolig":				"#E6E600",
			"02 Fritidsbolig":		"#FFCC33",
			"03 Næring":			"#9966CC",
			"04 Samferdsel":		"#999999",
			"05 Annen utbygging":	"#CCFFFF",
			"06 Udefinert":			"#4D4D4D"
		},
		"planalder": {
			"Nyere planregler":		"#FEFEFE",
			"Nyere enn 2008":		"#FEFEFE",
			"Eldre enn 2008":		"#CC9900",
			"Eldre enn 1985":		"#FF0000",
			"Manglende planalder":	"#990099"
		},
		"plankilde": {
			"Kommuneplan":				"#00B300",
			"Kommuneplan (delplan)":	"#CC0099",
			"Reguleringsplan":			"#0099CC",
			//
			"KpArealformålOmråde":		"#00B300",
			"KpArealbrukOmråde":		"#00B300",
			"RpArealformålOmråde":		"#0099CC",
			"RbFormålOmråde":			"#0099CC"
		},
		"arealstatus": {
			"Nåværende":					"#808080",
			"Framtidig":					"#00CCCC",
			"Videreutvikling av nåværende":	"#CCCC00",
			//
			"1":	"#808080",
			"2":	"#00CCCC",
			"3":	"#CCCC00"
		},
		"plantype": {
			"Kommuneplanens arealdel":				"#00B300",
			"Kommunedelplan":						"#CC9900",
			"Mindre endring av kommune(del)plan":	"#CCCC00",
			//
			"20":	"#00B300",
			"21":	"#CC9900",
			"22":	"#CCCC00"
		},
		"planstatus": {
			"Planlegging igangsatt":		"#CCCC00",
			"Planforslag":					"#00CCCC",
			"Endelig vedtatt arealplan":	"#00B300",
			//
			"1":	"#CCCC00",
			"2":	"#00CCCC",
			"3":	"#00B300"
		},
		"planbestemmelse": {
			"Med bestemmelser som egen tekst":			"#CCCC00",
			"Uten bestemmelser":						"#808080",
			"Planbestemmelser fremgår kun av kartet":	"#00CCCC",
			"Planbestemmelser både kart og tekst":		"#00B300",
			//
			"1":	"#CCCC00",
			"2":	"#808080",
			"3":	"#00CCCC",
			"4":	"#00B300"
		},
		"eierform": {
			"Offentlig formål":	"#00CCCC",
			"Felles":			"#CCCC00",
			"Annen eierform":	"#808080",
			//
			"1":	"#00CCCC",
			"2":	"#CCCC00",
			"3":	"#808080"
		},
		"lovreferanse": {
			"Manglende":			"#990099",
			"Før BL 1924":			"#990000",
			"BL 1924":				"#CC0000",
			"BL 1965":				"#FF0000",
			"PBL 1985":				"#997300",
			"PBL 1985 eller før":	"#CC9900",
			"PBL 2008":				"#FEFEFE",
			//
			"0":	"#990099",
			"1":	"#990000",
			"2":	"#CC0000",
			"3":	"#FF0000",
			"4":	"#997300",
			"5":	"#CC9900",
			"6":	"#FEFEFE"
		}
	},
	_OVERLAPP_COLOR = "#CC0000";

const _FELT_PLAN = {
	"planid": "Plan-ID",
	"fylke": "Fylke",
	"kommune": "Kommune",
	"arealformaal": "Arealformål",
	"arealformaalkode": "Arealformålskode",
	"arealformaalsgruppe": "Arealformålsgruppe",
	"arealklasse": "Arealklasse",
	"arealstatus": "Arealstatus",
	"ikrafttredelsesdato": "Ikrafttredelsesdato",
	"lovreferanse": "Lovreferanse",
	"omradenavn": "Områdenavn",
	"planbestemmelse": "Planbestemmelse",
	"plankilde": "Plankilde",
	"plannavn": "Plannavn",
	"plannmosaikkdato": "Plannmosaikkdato",
	"planstatus": "Planstatus",
	"plantype": "Plantype",
	"planalder": "Planalder",
	"planlagt_areal_m2": "Planlagt areal (m<sup>2</sup>)",
	"bebygd_og_restareal_m2": "bebygd og restareal (m<sup>2</sup>)"
};
const _FELT_PLAN_MAP = {
	arealstatus: v => planreserve_arealstatus[v],
	lovreferanse: v => planreserve_lovreferanse[v],
	eierform: v => planreserve_eierform[v],
	planbestemmelse: v => planreserve_planbestemmelse[v],
	planstatus: v => planreserve_planstatus[v],
	plantype: v => planreserve_plantype[v]
};



function SVGToPNG(svgBase64, width) {
	return new Promise(resolve => {
		let img = document.createElement("img");
		img.onload = function () {
			document.body.appendChild(img);
			let canvas = document.createElement("canvas");
			let ratio = (img.clientWidth / img.clientHeight) || 1;
			document.body.removeChild(img);
			canvas.width = width;
			canvas.height = width / ratio;
			let ctx = canvas.getContext("2d");
			ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
			try {
				let data = canvas.toDataURL('image/png');
				resolve(data);
			}
			catch(e) { resolve(null); }
		};
		img.onerror = function() { resolve(null); };
		img.src = svgBase64;
	});
}
