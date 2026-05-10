/**
 * UM 租賃系統首頁前端邏輯（框架版）
 * 功能：
 * 1) 載入站點資料
 * 2) 依篩選條件查詢
 * 3) 渲染站點清單
 * 4) 同步更新地圖區塊文字（之後可替換為真實地圖 Marker）
 */

const stationListEl = document.getElementById('stationList');
const statusMessageEl = document.getElementById('statusMessage');
const filterFormEl = document.getElementById('filterForm');
const resetBtnEl = document.getElementById('resetBtn');
const mapViewEl = document.getElementById('mapView');

/**
 * 將文字做 HTML Escape，避免把後端資料直接插入造成 XSS
 */
function escapeHTML(str) {
    return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

/**
 * 將查詢參數組成 URLSearchParams
 */
function buildQueryParams(formData) {
    const params = new URLSearchParams();

    const stationID = formData.get('stationID')?.trim();
    const vehicleType = formData.get('vehicleType')?.trim();
    const brand = formData.get('brand')?.trim();

    if (stationID) params.append('stationID', stationID);
    if (vehicleType) params.append('vehicleType', vehicleType);
    if (brand) params.append('brand', brand);

    return params;
}

/**
 * 呼叫後端 API 取得站點資料
 * API: GET /api/stations.php
 */
async function fetchStations(params = new URLSearchParams()) {
    statusMessageEl.textContent = '資料載入中...';

    const endpoint = `./api/stations.php${params.toString() ? `?${params.toString()}` : ''}`;
    const response = await fetch(endpoint, {
        method: 'GET',
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    const result = await response.json();
    if (!result.success) {
        throw new Error(result.message || '查詢失敗');
    }

    return result.data;
}

/**
 * 渲染站點列表
 */
function renderStations(stations) {
    if (!Array.isArray(stations) || stations.length === 0) {
        stationListEl.innerHTML = '';
        statusMessageEl.textContent = '目前沒有符合條件的站點。';
        mapViewEl.innerHTML = '<p class="map-placeholder">目前沒有可顯示的站點標記。</p>';
        return;
    }

    const html = stations.map((station) => {
        const available = Number(station.availableVehicles || 0);
        const capacity = Number(station.capacity || 0);
        const usageRate = capacity > 0 ? Math.round((available / capacity) * 100) : 0;
        const badgeClass = available > 0 ? 'tag-ok' : 'tag-warn';
        const badgeText = available > 0 ? '可租借' : '無可用車輛';

        return `
            <li class="station-item">
                <h3>${escapeHTML(station.name)} (#${escapeHTML(station.id)})</h3>
                <p class="station-meta">容量：${capacity} 台</p>
                <p class="station-meta">可用：${available} 台 <span class="${badgeClass}">${badgeText}</span></p>
                <p class="station-meta">可用率：${usageRate}%</p>
                <p class="station-meta">座標：(${escapeHTML(station.latitude)}, ${escapeHTML(station.longitude)})</p>
            </li>
        `;
    }).join('');

    stationListEl.innerHTML = html;
    statusMessageEl.textContent = `共找到 ${stations.length} 個站點。`;

    // 框架版地圖內容：顯示前幾個站點座標
    const mapPreview = stations.slice(0, 6).map((s) => {
        return `${escapeHTML(s.name)}: (${escapeHTML(s.latitude)}, ${escapeHTML(s.longitude)})`;
    }).join('<br />');

    mapViewEl.innerHTML = `
        <p class="map-placeholder">
            站點標記預覽：<br />
            ${mapPreview}
        </p>
    `;
}

/**
 * 初始化頁面：首次載入全部站點
 */
async function initPage() {
    try {
        const stations = await fetchStations();
        renderStations(stations);
    } catch (error) {
        console.error(error);
        statusMessageEl.textContent = '站點資料載入失敗，請稍後再試。';
    }
}

// 送出篩選表單
filterFormEl.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(filterFormEl);
    const params = buildQueryParams(formData);

    try {
        const stations = await fetchStations(params);
        renderStations(stations);
    } catch (error) {
        console.error(error);
        statusMessageEl.textContent = `查詢失敗：${error.message}`;
    }
});

// 重設篩選條件
resetBtnEl.addEventListener('click', async () => {
    filterFormEl.reset();
    await initPage();
});

initPage();
