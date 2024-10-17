/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, February 2024                   *
*******************************************************************************/

"use strict";


function format_page(doc, format) {
	let settings = {
		"a4": {
			logo_w: 26.491, logo_h: 12,
			fontsize: 9,
			pt: 12, pb: 12, ps: 10, pe: 20
		},
		"a6": {
			logo_w: 67.455, logo_h: 30.555,
			fontsize: 16,
			pt: 20, pb: 20, ps: 20, pe: 35
		}
	};
	let s = settings[format],
		now = moment().format("DD MMM YYYY"),
		year = moment().format("YYYY");

	doc.addImage("assets/logo.png", "PNG", doc.getPageWidth() - s.logo_w - 6, 6, s.logo_w, s.logo_h);
	doc.setTextColor(150);
	doc.setFontSize( s.fontsize );
	doc.text(`Rapport produsert ${now}`, s.ps, s.pt);
	doc.text(`© ${year} Nordfjord EDB AS | Laget med GeoTales`, s.ps, doc.getPageHeight() - s.pb);
	doc.text(`Side ${ doc.getCurrentPageInfo().pageNumber }`, doc.getPageWidth() - s.pe, doc.getPageHeight() - s.pb);

	//doc.setTextColor(100);
	//doc.setFont("helvetica", "italic");
	//doc.text("ARV – Arealregnskap med Visualisering", s.ps, s.pt + 7);
}


function generate_report(plandata, overlapp) {
	// Generer PDF-rapport av regnskapet

	_PDF = new jspdf.jsPDF({
		putOnlyUsedFonts: true,
		orientation: "portrait",
		format: "a4"
	});

	let now = moment().format("DD MMM YYYY"), c;
	let filename = `${_ACCOUNT_INFO.title}-${now}`;


	format_page(_PDF, "a4"); c = 0;

	_PDF.setTextColor(100);
	_PDF.setFontSize(15);
	_PDF.setFont("helvetica", "normal");
	_PDF.text(`Arealregnskap for ${_ORG_INFO.name == "__all__" ? "Utsira kommune" : _ORG_INFO.name}`, 20, c += 38);
	/*_PDF.textWithLink(
		`${_ORG_INFO.name == "__all__" ? "Utsira kommune" : _ORG_INFO.name} ${_ORG_INFO.kommunenummer ? `– ${_ORG_INFO.kommunenummer}` : ""}`,
		63, 38,
		{ url: `https://data.brreg.no/enhetsregisteret/oppslag/enheter/${_ORG_INFO["org.nr."]}` }
	);*/

	_PDF.setTextColor(0);
	_PDF.setFontSize(24);
	_PDF.setFont("helvetica", "bold");
	_PDF.text(_ACCOUNT_INFO.title, 20, c += 15);
	_PDF.setFontSize(10);
	_PDF.setFont("helvetica", "normal");
	_PDF.setTextColor(80);
	_PDF.text(`Regnskap produsert: ${ moment(_ACCOUNT_INFO.created_date).format("DD MMM YYYY") }`, 20, c += 7);
	_PDF.setFontSize(12);
	_PDF.setTextColor(0);
	_PDF.text(_ACCOUNT_INFO.description, 25, c += 10);

	_PDF.setFontSize(10);
	_PDF.text(`Produsert av ${_USER_INFO.username}, ${_USER_INFO.email}`, 20, c = 240);
	_PDF.text(`Produsert for ${_ORG_INFO.name == "__all__" ? "Utsira kommune" : _ORG_INFO.name}`, 20, c += 5);
	_PDF.setTextColor(80);
	_PDF.text(`Kontaktperson: ${_ORG_INFO.contact}`, 25, c += 5);
	_PDF.text(`Organisasjonsnummer: ${_ORG_INFO["org.nr."]}`, 25, c += 5);
	_PDF.text(_ORG_INFO.kommunenummer ? `Kommunenummer: ${_ORG_INFO.kommunenummer}` : "", 25, c += 5);


	_PDF.addPage("a4", "portrait");
	format_page(_PDF, "a4"); c = 0;

	_PDF.setTextColor(60);
	_PDF.setFontSize(20);
	_PDF.text(`Innholdsfortegnelse`, 20, c += 38);
	_PDF.setTextColor(0);
	_PDF.setFontSize(14);
	_PDF.setFont("helvetica", "normal");
	_PDF.textWithLink("– Innledning", 25, c += 15, { pageNumber: 3 });
	_PDF.textWithLink("– Arealbalanse", 25, c += 7, { pageNumber: 3 });
	_PDF.textWithLink("– Innledning", 25, c += 7, { pageNumber: 3 });
	_PDF.textWithLink("– Innledning", 25, c += 7, { pageNumber: 3 });


	_PDF.addPage("a4", "portrait");
	format_page(_PDF, "a4"); c = 0;

	_PDF.setTextColor(60);
	_PDF.setFontSize(20);
	_PDF.text(`Innledning`, 20, c += 38);
	_PDF.setTextColor(60);
	_PDF.setFontSize(14);
	_PDF.setFont("helvetica", "normal");
	_PDF.text(`Arealregnskapet viser arealreserven fra gjeldende kommuneplan og
arealformålsendringer fra gjeldende til foreslåtte planer.
Disse planområdene er analysert opp mot valgte interesseområder for å
identifisere potensielle arealkonflikter.`, 25, c += 10);
	_PDF.setFontSize(10);
	_PDF.text(`Metoden for å produsere arealregnskap i ARV er basert på veileder fra KDD.
For en full beskrivelse av metoden, samt forventningene til arealregnskap, les veilederen`, 25, c += 25);
	_PDF.textWithLink("her.", 164, c + 4, { url: "https://www.regjeringen.no/no/dokumenter/arealregnskap-i-kommuneplan/id3017913/" });

	_PDF.setTextColor(0);
	_PDF.setFontSize(12);
	_PDF.setFont("helvetica", "bold");
	_PDF.text(`Oversikt over plandataene som inngikk i regnskapet:`, 20, c += 20);


	/*_PDF.setFont("helvetica", "normal");
	_PDF.text("Alle verdier i Dekar (daa)", 20, 110);

	let create_headers = hs => hs.map(h => ({
		id: h,
		name: h,
		prompt: h,
		width: 69,
		align: "center",
		margins: { left: 15, right: 15 }
	}));

	let grupper = groupProperty(plandata, "arealformaalsgruppe").sort(),
		verdier = [];

	let d = groupArray(plandata, "arealformaalsgruppe", "planlagt_m2"),
		s = 0;
	Object.keys(d).map(g => {
		s += d[g];
		d[g] = format_area(d[g] / 1000).toString();
	});
	verdier.push({
		"_": "Planlagt utbygd areal",
		...d,
		"SUM": format_area(s / 1000).toString()
	});

	for(let t of groupProperty(overlapp, "overlapp_type")) {
		let data = groupArray( overlapp.filter(f => f.overlapp_type == t), "formaalsgruppe", "areal_m2" ),
			sum = 0;
		Object.keys(data).map(g => { sum += data[g]; });

		let v = {
			"_": `Arealkonflikt ${t}`,
			"SUM": format_area(sum / 1000).toString()
		};
		for(let g of grupper) { v[g] = format_area((data[g] || 0) / 1000).toString(); }
		verdier.push(v);
	}

	d = groupArray( overlapp, "formaalsgruppe", "areal_m2" );
	Object.keys(d).map(g => { d[g] = format_area(d[g] / 1000).toString(); });
	verdier.push({
		"_": "SUM AREALKONFLIKTER",
		...d,
		"SUM": ""
	});
	grupper = create_headers([ "_", ...grupper, "SUM" ]);

	_PDF.table(20, 120, verdier, grupper, {
		headerBackgroundColor: "#f1c374",
		printHeaders: true,
		//autoSize: true
	});


	for(let graph of ["planformaal_pie", "planalder_pie", "overlapp_bar"]) {
		_PDF.addPage("a2", "landscape");
		format_page(_PDF);

		let svg = _GRAPHS[ graph ].getSVGForExport();
		let base64 = "data:image/svg+xml;base64," + window.btoa( unescape( encodeURIComponent( svg ) ) );
		let img = await SVGToPNG(base64, 1800);

		let h = _PDF.getPageHeight() - 55 - 40;
		let w = h * 1.5;
		_PDF.addImage(img, "PNG", 20, 55, w, h);
	}*/


	return _PDF.save(filename, { returnPromise: true });

}
