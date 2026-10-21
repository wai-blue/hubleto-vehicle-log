(function () {
  'use strict';

  if (window.__hubletoVehicleLogLoaded) return;
  window.__hubletoVehicleLogLoaded = true;

  const tripSpans = {
    trip_date: 3,
    id_vehicle: 5,
    id_driver: 4,
    time_start: 4,
    time_end: 4,
    duration_minutes: 4,
    route_from: 6,
    route_to: 6,
    purpose: 12,
    odometer_start_km: 3,
    odometer_end_km: 3,
    distance_km: 2,
    business_km: 2,
    private_km: 2,
    fuel_purchased_l: 4,
    electricity_purchased_kwh: 4,
    purchase_price_incl_vat: 4,
    expected_fuel_consumption_l: 6,
    expected_electricity_consumption_kwh: 6,
    notes: 12,
  };

  const vehicleSpans = {
    id_company: 4,
    registration_number: 4,
    vin: 4,
    make: 4,
    model_name: 4,
    propulsion_type: 4,
    fuel_consumption_l_per_100km: 6,
    electric_consumption_kwh_per_100km: 6,
    initial_odometer_km: 4,
    initial_fuel_level_l: 4,
    initial_battery_level_kwh: 4,
    is_active: 3,
  };

  function fieldWrapper(scope, field) {
    return Array.from(scope.querySelectorAll('.input-wrapper')).find((element) =>
      element.id && element.id.endsWith('_' + field)
    ) || null;
  }

  function findFormBody(wrapper) {
    return wrapper.closest('.modal-body, .form-body');
  }

  function decorateGrid(body, spans) {
    body.classList.add('vl-form-grid');
    Object.entries(spans).forEach(([field, span]) => {
      const wrapper = fieldWrapper(body, field);
      if (wrapper) wrapper.dataset.vlSpan = String(span);
    });
  }

  function addSection(body, beforeField, title, icon) {
    const wrapper = fieldWrapper(body, beforeField);
    if (!wrapper || body.querySelector('[data-vl-section="' + beforeField + '"]')) return;

    const section = document.createElement('div');
    section.className = 'vl-form-section';
    section.dataset.vlSection = beforeField;
    section.innerHTML = '<i class="fas ' + icon + '"></i><span>' + title + '</span>';
    wrapper.insertAdjacentElement('beforebegin', section);
  }

  function addTripSections(body) {
    addSection(body, 'trip_date', 'Trip details', 'fa-calendar-day');
    addSection(body, 'route_from', 'Route and purpose', 'fa-route');
    addSection(body, 'odometer_start_km', 'Mileage', 'fa-tachometer-alt');
    addSection(body, 'fuel_purchased_l', 'Energy and costs', 'fa-gas-pump');
    addSection(body, 'notes', 'Notes', 'fa-sticky-note');
  }

  function addVehicleSections(body) {
    addSection(body, 'id_company', 'Vehicle details', 'fa-car');
    addSection(body, 'fuel_consumption_l_per_100km', 'Consumption', 'fa-tachometer-alt');
    addSection(body, 'initial_odometer_km', 'Opening values', 'fa-flag-checkered');
  }

  function editableElement(scope, field) {
    const wrapper = fieldWrapper(scope, field);
    if (!wrapper) return null;
    return wrapper.querySelector('.input-element input:not([type="hidden"]), .input-element textarea');
  }

  function setInputValue(scope, field, value) {
    const element = editableElement(scope, field);
    if (!element) return;
    const prototype = element instanceof HTMLTextAreaElement
      ? HTMLTextAreaElement.prototype
      : HTMLInputElement.prototype;
    const setter = Object.getOwnPropertyDescriptor(prototype, 'value')?.set;
    if (setter) setter.call(element, value == null ? '' : String(value));
    else element.value = value == null ? '' : String(value);
    element.dispatchEvent(new Event('input', { bubbles: true }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function getFormMeta(formUid) {
    return globalThis.hubleto?.reactElements?.[formUid] || null;
  }

  function setFields(formUid, body, values) {
    const form = getFormMeta(formUid);
    if (form?.changeRecord) {
      form.changeRecord(values);
      return;
    }

    Object.entries(values).forEach(([field, value]) => setInputValue(body, field, value));
  }

  function coordinate(value, min, max) {
    if (value === null || value === undefined || value === '') return null;
    const number = Number(value);
    return Number.isFinite(number) && number >= min && number <= max ? number : null;
  }

  function createButton(text, title, icon = '') {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'btn btn-small btn-transparent';
    button.title = title || text;
    if (icon) {
      const iconElement = document.createElement('i');
      iconElement.className = 'fas ' + icon;
      button.append(iconElement);
    }
    button.append(document.createTextNode(text));
    return button;
  }

  function createRoutePicker(body, insertAfter, formUid) {
    const picker = document.createElement('div');
    picker.className = 'vl-route-picker card';
    picker.dataset.vlRoutePicker = 'true';

    const header = document.createElement('div');
    header.className = 'vl-route-picker__header';
    const title = document.createElement('div');
    title.className = 'vl-route-picker__title';
    title.textContent = 'Route on map';
    const actions = document.createElement('div');
    actions.className = 'vl-route-picker__actions';
    const startButton = createButton('Select start', 'The next map click sets the route start', 'fa-map-marker-alt');
    const endButton = createButton('Select end', 'The next map click sets the route end', 'fa-flag-checkered');
    const clearButton = createButton('Clear', 'Remove both selected points', 'fa-eraser');
    actions.append(startButton, endButton, clearButton);
    header.append(title, actions);

    const map = document.createElement('div');
    map.className = 'vl-route-picker__map';
    map.tabIndex = 0;
    const tiles = document.createElement('div');
    tiles.className = 'vl-route-picker__tiles';
    const overlay = document.createElement('svg');
    overlay.classList.add('vl-route-picker__overlay');
    const routeLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    routeLine.setAttribute('stroke', '#2563eb');
    routeLine.setAttribute('stroke-width', '4');
    routeLine.setAttribute('stroke-dasharray', '8 5');
    routeLine.setAttribute('stroke-linecap', 'round');
    overlay.appendChild(routeLine);
    const startMarker = document.createElement('div');
    startMarker.className = 'vl-route-picker__marker vl-route-picker__marker--start';
    startMarker.innerHTML = '<span>A</span>';
    const endMarker = document.createElement('div');
    endMarker.className = 'vl-route-picker__marker vl-route-picker__marker--end';
    endMarker.innerHTML = '<span>B</span>';

    const zoom = document.createElement('div');
    zoom.className = 'vl-route-picker__zoom';
    const zoomIn = createButton('+', 'Zoom in');
    const zoomOut = createButton('−', 'Zoom out');
    zoom.append(zoomIn, zoomOut);
    const attribution = document.createElement('div');
    attribution.className = 'vl-route-picker__attribution';
    attribution.innerHTML = '© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>';
    map.append(tiles, overlay, startMarker, endMarker, zoom, attribution);

    const status = document.createElement('div');
    status.className = 'vl-route-picker__status';
    picker.append(header, map, status);
    insertAfter.insertAdjacentElement('afterend', picker);

    let start = null;
    let end = null;
    let mode = 'start';
    let zoomLevel = 7;
    let center = { lat: 48.67, lon: 19.50 };
    let drag = null;
    let renderQueued = false;

    function world(point, level) {
      const scale = 256 * Math.pow(2, level);
      const sin = Math.sin(point.lat * Math.PI / 180);
      return {
        x: (point.lon + 180) / 360 * scale,
        y: (0.5 - Math.log((1 + sin) / (1 - sin)) / (4 * Math.PI)) * scale,
      };
    }

    function geo(point, level) {
      const scale = 256 * Math.pow(2, level);
      const lon = point.x / scale * 360 - 180;
      const n = Math.PI - 2 * Math.PI * point.y / scale;
      const lat = 180 / Math.PI * Math.atan(0.5 * (Math.exp(n) - Math.exp(-n)));
      return { lat: Math.max(-85.0511, Math.min(85.0511, lat)), lon };
    }

    function screen(point) {
      const centerWorld = world(center, zoomLevel);
      const pointWorld = world(point, zoomLevel);
      return {
        x: pointWorld.x - centerWorld.x + map.clientWidth / 2,
        y: pointWorld.y - centerWorld.y + map.clientHeight / 2,
      };
    }

    function pointAt(clientX, clientY) {
      const rect = map.getBoundingClientRect();
      const centerWorld = world(center, zoomLevel);
      return geo({
        x: centerWorld.x + clientX - rect.left - rect.width / 2,
        y: centerWorld.y + clientY - rect.top - rect.height / 2,
      }, zoomLevel);
    }

    function updateMode() {
      startButton.classList.toggle('btn-primary', mode === 'start');
      startButton.classList.toggle('btn-transparent', mode !== 'start');
      endButton.classList.toggle('btn-primary', mode === 'end');
      endButton.classList.toggle('btn-transparent', mode !== 'end');
      if (!start) status.textContent = 'Click the map to select the route start.';
      else if (!end) status.textContent = 'Start saved. Click the map to select the route end.';
      else status.textContent = 'Both points are selected. Drag or zoom the map, or choose which point to change.';
    }

    function updateMarkers() {
      const a = start ? screen(start) : null;
      const b = end ? screen(end) : null;
      startMarker.style.display = a ? 'block' : 'none';
      endMarker.style.display = b ? 'block' : 'none';
      if (a) {
        startMarker.style.left = a.x + 'px';
        startMarker.style.top = a.y + 'px';
      }
      if (b) {
        endMarker.style.left = b.x + 'px';
        endMarker.style.top = b.y + 'px';
      }
      routeLine.style.display = a && b ? 'block' : 'none';
      if (a && b) {
        routeLine.setAttribute('x1', String(a.x));
        routeLine.setAttribute('y1', String(a.y));
        routeLine.setAttribute('x2', String(b.x));
        routeLine.setAttribute('y2', String(b.y));
      }
    }

    function render() {
      renderQueued = false;
      if (!map.clientWidth || !map.clientHeight) return;
      const centerWorld = world(center, zoomLevel);
      const firstX = Math.floor((centerWorld.x - map.clientWidth / 2) / 256) - 1;
      const lastX = Math.floor((centerWorld.x + map.clientWidth / 2) / 256) + 1;
      const firstY = Math.floor((centerWorld.y - map.clientHeight / 2) / 256) - 1;
      const lastY = Math.floor((centerWorld.y + map.clientHeight / 2) / 256) + 1;
      const count = Math.pow(2, zoomLevel);
      const fragment = document.createDocumentFragment();
      for (let y = firstY; y <= lastY; y += 1) {
        if (y < 0 || y >= count) continue;
        for (let x = firstX; x <= lastX; x += 1) {
          const image = document.createElement('img');
          const wrappedX = ((x % count) + count) % count;
          image.src = 'https://tile.openstreetmap.org/' + zoomLevel + '/' + wrappedX + '/' + y + '.png';
          image.alt = '';
          image.draggable = false;
          image.style.left = (x * 256 - centerWorld.x + map.clientWidth / 2) + 'px';
          image.style.top = (y * 256 - centerWorld.y + map.clientHeight / 2) + 'px';
          fragment.appendChild(image);
        }
      }
      tiles.replaceChildren(fragment);
      overlay.setAttribute('width', String(map.clientWidth));
      overlay.setAttribute('height', String(map.clientHeight));
      updateMarkers();
    }

    function queueRender() {
      if (renderQueued) return;
      renderQueued = true;
      requestAnimationFrame(render);
    }

    async function reverseGeocode(point, routeField) {
      setFields(formUid, body, {
        [routeField]: point.lat.toFixed(7) + ', ' + point.lon.toFixed(7),
      });
      const projectUrl = window.hubleto?.config?.projectUrl || '';
      const url = projectUrl + '/vehicle-log/api/reverse-geocode?__IS_AJAX__=1&latitude='
        + encodeURIComponent(point.lat.toFixed(7)) + '&longitude=' + encodeURIComponent(point.lon.toFixed(7));
      try {
        const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
        const data = await response.json();
        if (response.ok && data.address) setFields(formUid, body, {[routeField]: data.address});
      } catch (error) {
        console.warn('Vehicle log: reverse geocoding failed.', error);
      }
    }

    function selectPoint(point) {
      point.lat = Math.max(-85.0511, Math.min(85.0511, point.lat));
      point.lon = ((point.lon + 540) % 360) - 180;
      if (mode === 'start') {
        start = point;
        setFields(formUid, body, {
          start_latitude: point.lat.toFixed(7),
          start_longitude: point.lon.toFixed(7),
        });
        reverseGeocode(point, 'route_from');
        mode = 'end';
      } else {
        end = point;
        setFields(formUid, body, {
          end_latitude: point.lat.toFixed(7),
          end_longitude: point.lon.toFixed(7),
        });
        reverseGeocode(point, 'route_to');
      }
      updateMode();
      queueRender();
    }

    startButton.addEventListener('click', () => { mode = 'start'; updateMode(); });
    endButton.addEventListener('click', () => { mode = 'end'; updateMode(); });
    clearButton.addEventListener('click', () => {
      start = null;
      end = null;
      mode = 'start';
      setFields(formUid, body, {
        start_latitude: null,
        start_longitude: null,
        end_latitude: null,
        end_longitude: null,
        route_from: '',
        route_to: '',
      });
      updateMode();
      queueRender();
    });

    zoomIn.addEventListener('click', () => { zoomLevel = Math.min(19, zoomLevel + 1); queueRender(); });
    zoomOut.addEventListener('click', () => { zoomLevel = Math.max(3, zoomLevel - 1); queueRender(); });

    map.addEventListener('wheel', (event) => {
      event.preventDefault();
      const nextZoom = Math.max(3, Math.min(19, zoomLevel + (event.deltaY < 0 ? 1 : -1)));
      if (nextZoom === zoomLevel) return;
      const rect = map.getBoundingClientRect();
      const underPointer = pointAt(event.clientX, event.clientY);
      const nextPointWorld = world(underPointer, nextZoom);
      zoomLevel = nextZoom;
      center = geo({
        x: nextPointWorld.x - (event.clientX - rect.left - rect.width / 2),
        y: nextPointWorld.y - (event.clientY - rect.top - rect.height / 2),
      }, zoomLevel);
      queueRender();
    }, { passive: false });

    map.addEventListener('pointerdown', (event) => {
      if (event.target.closest('button, a')) return;
      map.setPointerCapture(event.pointerId);
      drag = {
        pointerId: event.pointerId,
        x: event.clientX,
        y: event.clientY,
        centerWorld: world(center, zoomLevel),
        moved: false,
      };
    });

    map.addEventListener('pointermove', (event) => {
      if (!drag || drag.pointerId !== event.pointerId) return;
      const dx = event.clientX - drag.x;
      const dy = event.clientY - drag.y;
      if (Math.abs(dx) + Math.abs(dy) > 4) drag.moved = true;
      if (drag.moved) {
        map.classList.add('is-dragging');
        center = geo({ x: drag.centerWorld.x - dx, y: drag.centerWorld.y - dy }, zoomLevel);
        queueRender();
      }
    });

    map.addEventListener('pointerup', (event) => {
      if (!drag || drag.pointerId !== event.pointerId) return;
      const moved = drag.moved;
      drag = null;
      map.classList.remove('is-dragging');
      if (!moved) selectPoint(pointAt(event.clientX, event.clientY));
    });

    map.addEventListener('pointercancel', () => {
      drag = null;
      map.classList.remove('is-dragging');
    });

    if (window.ResizeObserver) new ResizeObserver(queueRender).observe(map);

    const record = getFormMeta(formUid)?.getRecord?.() || {};
    const startLatitude = coordinate(record.start_latitude, -90, 90);
    const startLongitude = coordinate(record.start_longitude, -180, 180);
    const endLatitude = coordinate(record.end_latitude, -90, 90);
    const endLongitude = coordinate(record.end_longitude, -180, 180);

    if (startLatitude !== null && startLongitude !== null) {
      start = {lat: startLatitude, lon: startLongitude};
    }
    if (endLatitude !== null && endLongitude !== null) {
      end = {lat: endLatitude, lon: endLongitude};
    }
    if (start || end) {
      mode = start && !end ? 'end' : 'start';
      zoomLevel = 12;
      center = start && end
        ? {lat: (start.lat + end.lat) / 2, lon: (start.lon + end.lon) / 2}
        : start || end;
    }

    window.setTimeout(queueRender, 0);
    updateMode();
  }

  function enhanceTripForm(routeFromWrapper) {
    const body = findFormBody(routeFromWrapper);
    const routeToWrapper = body ? fieldWrapper(body, 'route_to') : null;
    const purposeWrapper = body ? fieldWrapper(body, 'purpose') : null;
    if (!body || !routeToWrapper || !purposeWrapper || body.querySelector('[data-vl-route-picker="true"]')) return;

    const suffix = '_route_from';
    const formUid = routeFromWrapper.id.slice(0, -suffix.length);
    routeFromWrapper.dataset.vlEnhanced = 'true';
    decorateGrid(body, tripSpans);
    addTripSections(body);
    createRoutePicker(body, purposeWrapper, formUid);
  }

  function enhanceVehicleForm(registrationWrapper) {
    if (registrationWrapper.dataset.vlEnhanced === 'true') return;
    const body = findFormBody(registrationWrapper);
    if (!body || !fieldWrapper(body, 'vin') || !fieldWrapper(body, 'propulsion_type')) return;
    registrationWrapper.dataset.vlEnhanced = 'true';
    decorateGrid(body, vehicleSpans);
    addVehicleSections(body);
  }

  function enhanceForms() {
    document.querySelectorAll('.input-wrapper').forEach((wrapper) => {
      if (wrapper.id?.endsWith('_route_from')) enhanceTripForm(wrapper);
      if (wrapper.id?.endsWith('_registration_number')) enhanceVehicleForm(wrapper);
    });
  }

  const observer = new MutationObserver(enhanceForms);
  observer.observe(document.documentElement, { childList: true, subtree: true });
  enhanceForms();
})();
