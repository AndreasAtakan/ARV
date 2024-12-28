#!/usr/bin/env python3

# sudo pip install geopandas sqlalchemy geoalchemy2

import os
import sys
import argparse
from datetime import datetime
import fiona
import pandas as pd
import geopandas as gpd
from sqlalchemy import create_engine

TESTING = True

LAYER_OMRAADE = [
	'Område', 'Omraade', 'Omrade',
	'område', 'omraade', 'omrade'
]
LAYER_FORMAAL = [
	'ArealformålOmråde', 'ArealformaalOmraade', 'ArealformalOmrade',
	'Arealformålområde', 'Arealformaalomraade', 'Arealformalomrade',
	'arealformålområde', 'arealformaalomraade', 'arealformalomrade'
]

FIELD_PLANID = [
	'Planidentifikasjon', 'planidentifikasjon',
	'PlanID', 'PlanId', 'planid'
]
FIELD_LOKALID = [
	'LokalId', 'Lokalid',
	'lokalId', 'lokalid'
]
FIELD_VERSJON = [
	'VersjonId', 'Versjonid',
	'versjonId', 'versjonid'
]
FIELD_FORMAAL = [
	'Arealformål', 'Arealformaal', 'Arealformal',
	'arealformål', 'arealformaal', 'arealformal',
	'Arealbruk',
	'arealbruk'
]
FIELD_AREALSTATUS = [
	'Arealstatus', 'Arealbruksstatus', 'Arealbrukstatus',
	'arealstatus', 'arealbruksstatus', 'arealbrukstatus'
]
FIELD_IKRAFT_DATO = [
	'Ikrafttredelsesdato', 'Ikrafttredingsdato', 'Ikraftdato',
	'ikrafttredelsesdato', 'ikrafttredingsdato', 'ikraftdato',
	'FørsteDigitaliseringsdato', 'ForsteDigitaliseringsdato', 'FoersteDigitaliseringsdato',
	'førsteDigitaliseringsdato', 'forsteDigitaliseringsdato', 'foersteDigitaliseringsdato',
	'førstedigitaliseringsdato', 'forstedigitaliseringsdato', 'foerstedigitaliseringsdato',
	'Oppdateringsdato',
	'oppdateringsdato'
]
FIELD_EIERFORM = [ 'Eierform', 'eierform' ]
FIELD_BESKRIVELSE = [ 'Beskrivelse', 'beskrivelse' ]
FIELD_OMRAADENAVN = [
	'OmrådeNavn', 'OmraadeNavn',
	'områdeNavn', 'omraadeNavn',
	'Områdenavn', 'Omraadenavn',
	'områdenavn', 'omraadenavn'
]
FIELD_PLANNAVN = [ 'Plannavn', 'plannavn' ]
FIELD_PLANTYPE = [ 'Plantype', 'plantype' ]
FIELD_PLANSTATUS = [ 'Planstatus', 'planstatus' ]
FIELD_PLANBESTEMMELSE = [ 'Planbestemmelse', 'planbestemmelse' ]
FIELD_LOVREFERANSE = [ 'Lovreferanse', 'lovreferanse' ]


make_date = lambda datestr : pd.to_datetime(datestr) #time.strptime(datestr, '%Y-%m-%d')
is_sublist = lambda _list, sub_list : all(x in _list for x in sub_list)
def match_list(_list, sub_list):
	for x in sub_list:
		if x in _list: return x


parser = argparse.ArgumentParser()
parser.add_argument('_dato',type=make_date)
args = parser.parse_args()
_dato = args._dato

files_dir = '_files/'
_files = [
	os.path.join(files_dir, f)
		for f in os.listdir(files_dir)
			if f.lower().endswith(('.zip', '.shp', '.sos', '.gml', '.gpkg'))
]

_planer = None

for file in _files:
	layers = fiona.listlayers(file)
	pt = layers[0][:2]

	if pt.lower() != 'kp' and pt.lower() != 'rp':
		raise Exception('Ukjent plantype! Ikke kommuneplan/-delplan eller reguleringsplan')

	layers = list(map(lambda s : s[2:], layers))

	l = match_list(layers, LAYER_OMRAADE)
	_omraade = gpd.read_file(file, layer=f'{pt}{l}')

	l = match_list(layers, LAYER_FORMAAL)
	_formaal = gpd.read_file(file, layer=f'{pt}{l}')

	f = match_list(_omraade.columns.tolist(), FIELD_PLANID)
	_omraade[f] = _omraade[f].astype(str)
	f = match_list(_formaal.columns.tolist(), FIELD_PLANID)
	_formaal[f] = _formaal[f].astype(str)
	_formaal = _formaal.merge(_omraade, on=f, suffixes=('', '_y'), copy=False)
	_formaal = _formaal.drop(columns=[ col for col in _formaal.columns if col.endswith('_y') ])
	_formaal = _formaal.drop_duplicates()


	f = match_list(_formaal.columns.tolist(), FIELD_IKRAFT_DATO)
	_formaal['planlegging_status'] = 'Ny plan'
	_formaal[f] = pd.to_datetime(_formaal[f])
	_formaal.loc[_formaal[f] < _dato, 'planlegging_status'] = 'Tidligere plan'
	_formaal[f] = _formaal[f].astype(str)


	f = match_list(_formaal.columns.tolist(), FIELD_AREALSTATUS)
	if pt.lower() == 'rp': _formaal[f] = 1

	_formaal['regplan_pri'] = True if file[:4] == 'pri_' else False


	_formaal['plankilde'] = 'Kommuneplan' if pt.lower() == 'kp' else 'Reguleringsplan'
	_formaal['planreservedato'] = datetime.now().strftime("%d-%m-%YT%H:%M:%S")


	c = _formaal.columns.tolist()
	_formaal = _formaal.rename(columns={
		match_list(c, FIELD_PLANID): 'planidentifikasjon',
		match_list(c, FIELD_LOKALID): 'lokalid',
		match_list(c, FIELD_VERSJON): 'versjonid',
		match_list(c, FIELD_FORMAAL): 'arealformaal',
		match_list(c, FIELD_AREALSTATUS): 'arealstatus',
		match_list(c, FIELD_IKRAFT_DATO): 'ikraft_dato',
		match_list(c, FIELD_EIERFORM): 'eierform',
		match_list(c, FIELD_BESKRIVELSE): 'beskrivelse',
		match_list(c, FIELD_OMRAADENAVN): 'områdenavn',
		match_list(c, FIELD_PLANNAVN): 'plannavn',
		match_list(c, FIELD_PLANTYPE): 'plantype',
		match_list(c, FIELD_PLANSTATUS): 'planstatus',
		match_list(c, FIELD_PLANBESTEMMELSE): 'planbestemmelse',
		match_list(c, FIELD_LOVREFERANSE): 'lovreferanse'
	})

	keep_cols = [
		'planidentifikasjon',
		'planlegging_status',
		'plankilde',
		'planreservedato',
		'lokalid',
		'versjonid',
		'arealformaal',
		'arealstatus',
		'ikraft_dato',
		'eierform',
		'beskrivelse',
		'områdenavn',
		'plannavn',
		'plantype',
		'planstatus',
		'planbestemmelse',
		'lovreferanse',
		'geometry',
		'regplan_pri'
	]
	_formaal = _formaal.drop(columns=[ col for col in _formaal.columns if col not in keep_cols ])


	#_formaal.set_crs(crs='EPSG:25833', allow_override=True)


	if _planer is None: _planer = _formaal
	else: _planer = pd.concat([_planer, _formaal], ignore_index=True)
		#_planer = _planer.append(_formaal, ignore_index=True)


	#f = match_list(_formaal.columns.tolist(), FORMAAL_FELT)
	#_f = _formaal[ _formaal['planlegging_status'] == 'Ny plan' ]
	#_o = gpd.overlay(
	#	_planer[ _planer['planlegging_status'] == 'Tidligere plan' ],
	#	_f,
	#	how='intersection'
	#)
	#_formaal = _formaal[~_formaal.index.isin(_o.index)]
	#for index1, row1 in _o.iterrows():
	#	for index2, row2 in _f.iterrows():
	#		if row1[f] == row2[f]: _formaal.drop(index2)


	# fjerne kryssende deler av områder hvor dato er nyere i denne planen
	#_overlay = gpd.overlay(_planer, _formaal, how='intersection', keep_geom_type=False)
	#_overlay = _overlay[ _overlay['planlegging_status_1'] == _overlay['planlegging_status_2'] ]
	#_overlay[f'{f}_1'] = pd.to_datetime(_overlay[f'{f}_1'])
	#_overlay[f'{f}_2'] = pd.to_datetime(_overlay[f'{f}_2'])
	#_overlay = _overlay[ _overlay[f'{f}_1'] >= _overlay[f'{f}_2'] ]
	#_formaal['geometry'] = _formaal['geometry'].apply( lambda g : g.difference(_overlay.unary_union) )
	#_formaal = _formaal[ ~_formaal.is_empty ]

	#for index1, row1 in _planer.iterrows():
	#	for index2, row2 in _formaal.iterrows():
	#		if row1['planlegging_status'] == row2['planlegging_status'] \
	#		and row1[f] > row2[f] \
	#		and row1['geometry'].intersects(row2['geometry']):
	#			row1['geometry'] = row1['geometry'].difference(row2['geometry'])
	#			if not row1['geometry'].is_empty:
	#				_planer.loc[index1] = row1
	# fjerne kryssende deler av områder hvor dato er nyere i andre planen
	#for index1, row1 in _planer.iterrows():
	#	for index2, row2 in _formaal.iterrows():
	#		if row1['planlegging_status'] == row2['planlegging_status'] \
	#		and row1[f] < row2[f] \
	#		and row1['geometry'].intersects(row2['geometry']):
	#			row2['geometry'] = row2['geometry'].difference(row1['geometry'])
	#			if not row2['geometry'].is_empty:
	#				_planer.loc[index2] = row2


# sammenstille alle tidligere planer og nye planer til hver sin planmosaikk
#	dette gjøres slik: kommuneplaner og -delplaner sammenstilles slik at nyeste planområde gjelder
#						resultatet fra dette sammenstilles med reguleringsplanene basert på brukervalg på om de tar forrang eller ikke
#						(hvert planområde i hver av planmosaikkene får et statusfelt med verdien 'tidligere plan' eller 'ny plan')


port = '63333' if TESTING else '5432'
db = create_engine(f'postgresql://postgres:vleowemnxoyvq@localhost:{port}/arv')
_planer.to_postgis('__planer', con=db, if_exists='replace')

#_planer.to_file('_planer.gpkg', driver='GPKG', layer='_planer')











#plan = gpd.read_file(
#	'../data/Plan_1151_Utsira_25832_Kommuneplaner_Kommuneplan_SOSI.sos',
#	driver='SOSI'
#)
