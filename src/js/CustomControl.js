/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, September 2023                  *
*******************************************************************************/

"use strict";

class CustomBtn {
	_className = "";
	_title = "";
	_icon;
	_eventHandler;
	_map = undefined;

	constructor({ className, title, icon, eventHandler }) {
		this._className = className;
		this._title = title;
		this._icon = icon;
		this._eventHandler = eventHandler;
	}

	onAdd(map) {
		this._icn = document.createElement("i");
		this._icn.className = "fa-solid fa-" + this._icon;

		this._btn = document.createElement("button");
		this._btn.className = "mapboxgl-ctrl-icon";
		this._btn.type = "button";
		this._btn.title = this._title;
		this._btn.onclick = this._eventHandler;
		this._btn.appendChild(this._icn);

		this._container = document.createElement("div");
		this._container.className = "mapboxgl-ctrl-group mapboxgl-ctrl mapboxgl-ctrl-custom " + this._className || "";
		this._container.appendChild(this._btn);

		this._map = map;
		return this._container;
	}

	onRemove() {
		this._container.parentNode.removeChild(this._container);
		this._map = undefined;
	}
}

class CustomBtnGroup {
	_className = "";
	_title = "";
	_btns;
	_map = undefined;

	constructor({ className, title, btns }) {
		this._className = className;
		this._title = title;
		this._btns = btns;
	}

	onAdd(map) {
		this._container = document.createElement("div");
		this._container.className = "btn-group btn-group-sm mapboxgl-ctrl-group mapboxgl-ctrl mapboxgl-ctrl-custom " + this._className || "";
		this._container.role = "group";
		this._container.ariaLabel = this._title;

		for(let e of this._btns.map(o => {
			let b = document.createElement("button");
			b.type = "button";
			b.className = "btn btn-outline-secondary " + o.className || "";
			b.innerHTML = o.title;
			b.onclick = o.eventHandler;
			return b;
		}))
		{ this._container.appendChild(e); }

		this._map = map;
		return this._container;
	}

	onRemove() {
		this._container.parentNode.removeChild(this._container);
		this._map = undefined;
	}
}

class CustomDropdown {
	_className = "";
	_title = "";
	_options;
	_eventHandler;
	_map = undefined;

	constructor({ className, title, options, eventHandler }) {
		this._className = className;
		this._title = title;
		this._options = options;
		this._eventHandler = eventHandler;
	}

	onAdd(map) {
		this._t = document.createElement("option");
		this._t.innerHTML = this._title;
		this._t.disabled = true;
		this._t.selected = true;

		this._select = document.createElement("select");
		this._select.className = "form-select";
		this._select.onchange = this._eventHandler;
		this._select.appendChild(this._t);
		for(let e of this._options.map(o => {
			let t = document.createElement("option");
			t.innerHTML = o; t.value = o;
			return t;
		}))
		{ this._select.appendChild(e); }

		this._container = document.createElement("div");
		this._container.className = "mapboxgl-ctrl-group mapboxgl-ctrl mapboxgl-ctrl-custom " + this._className || "";
		this._container.appendChild(this._select);

		this._map = map;
		return this._container;
	}

	onRemove() {
		this._container.parentNode.removeChild(this._container);
		this._map = undefined;
	}
}
