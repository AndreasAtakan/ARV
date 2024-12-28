/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, January 2024                    *
*******************************************************************************/

"use strict";

class CustomResizeObserver {
	_o;
	_endEv;

	constructor({ element, resizeFunction, endEvent }) {
		//

		this._endEv = undefined;
		this._o = new ResizeObserver(mutations => {
			resizeFunction();

			clearTimeout(this._endEv);
			this._endEv = setTimeout(endEvent, 250);
		});
		this._o.observe( $(element)[0] );
	}
}
