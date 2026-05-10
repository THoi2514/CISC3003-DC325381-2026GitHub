<?php
declare(strict_types=1);
$projectRoot = dirname(__DIR__, 2);

/** Log fatals when the host hides errors (empty HTTP 500 body). Check .dashboard_fatal.log then delete it. */
register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err === null) {
        return;
    }
    if (!in_array((int) $err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    @file_put_contents(
        $projectRoot . '/.dashboard_fatal.log',
        date('c') . ' ' . $err['message'] . ' @ ' . $err['file'] . ':' . $err['line'] . "\n",
        FILE_APPEND | LOCK_EX
    );
});

require_once $projectRoot . '/includes/auth.php';
requireUserSession();
startAuthSession();
if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
        $_SESSION['csrf_token'] = bin2hex(hash('sha256', uniqid((string) mt_rand(), true) . microtime(true), true));
    }
}
$csrfToken = (string)$_SESSION['csrf_token'];

$name = (string)($_SESSION['full_name'] ?? $_SESSION['campus_id'] ?? 'User');
$role = (string)($_SESSION['role'] ?? 'guest');
$activePage = 'dashboard';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require $projectRoot . '/includes/favicon_links.php'; ?>
    <title>UM Rental · My rides</title>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <link rel="stylesheet" href="./assets/um_landing.css?v=<?= file_exists($projectRoot . '/assets/um_landing.css') ? filemtime($projectRoot . '/assets/um_landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/home-landing.css?v=<?= file_exists($projectRoot . '/assets/home-landing.css') ? filemtime($projectRoot . '/assets/home-landing.css') : time() ?>">
    <link rel="stylesheet" href="./assets/dashboard.css?v=<?= file_exists($projectRoot . '/assets/dashboard.css') ? filemtime($projectRoot . '/assets/dashboard.css') : time() ?>">
    <link rel="stylesheet" href="./assets/site-footer.css?v=<?= file_exists($projectRoot . '/assets/site-footer.css') ? filemtime($projectRoot . '/assets/site-footer.css') : time() ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
</head>
<body class="user-dashboard" data-map-editor="<?= in_array($role, ['staff', 'admin'], true) ? '1' : '0' ?>">
<?php require $projectRoot . '/includes/public_header.php'; ?>

<main class="wrap dashboard-main">
    <header class="dashboard-welcome">
        <p class="dashboard-kicker" data-i18n="dashboardKicker">UM Rental</p>
        <h1 class="dashboard-h1"><span data-i18n="dashboardWelcome">Welcome back</span>, <?= htmlspecialchars($name) ?></h1>
        <p class="dashboard-sub" data-i18n="dashboardSub">Choose where to start, pick an available bike or e-scooter, and track your trips — all in one place.</p>
    </header>

    <div id="miniPopup" class="mini-popup" role="alert" aria-live="assertive"></div>
    <section class="card search-card">
        <h2 data-i18n="searchTitle">Find a ride</h2>
        <div class="search-grid">
            <div>
                <label data-i18n="pickupLocationLabel" for="pickupLocation">Pickup location</label>
                <select id="pickupLocation"></select>
            </div>
            <div>
                <label data-i18n="pickupTimeLabel" for="pickupTime">Pickup time</label>
                <input id="pickupTime" type="datetime-local">
            </div>
            <div>
                <label data-i18n="dropoffTimeLabel" for="dropoffTime">Drop-off time</label>
                <input id="dropoffTime" type="datetime-local">
            </div>
            <div class="search-cta">
                <button id="searchRideBtn" data-i18n="searchRideBtn">Search</button>
            </div>
        </div>
        <div id="searchSummary" class="message"></div>
    </section>

    <section class="card" id="resultsCard">
        <h2 data-i18n="resultsTitle">Campus rental stations</h2>
        <p class="message station-pick-intro" data-i18n="stationPickIntro">Each stop shows how many bikes and e-scooters are available. Open a stop to choose a vehicle.</p>
        <div class="results-head">
            <div id="resultsCountText" class="message">0 stations</div>
        </div>
        <div id="stationPickCards" class="station-pick-grid" aria-label="Stations with availability"></div>

        <div id="vehicleListPanel" class="vehicle-list-panel is-hidden">
            <h3 class="vehicle-list-heading" id="vehicleListHeading">Vehicles at this station</h3>
            <div class="results-head">
                <div class="controls">
                    <label for="sortBy" data-i18n="sortByLabel">Sort by</label>
                    <select id="sortBy">
                        <option value="price_asc" data-i18n="sortPriceAsc">Price: low to high</option>
                        <option value="price_desc" data-i18n="sortPriceDesc">Price: high to low</option>
                    </select>
                </div>
            </div>
            <div class="controls">
                <select id="filterStation" aria-label="Station filter"></select>
                <select id="filterBrand"></select>
                <input id="filterBattery" type="number" min="0" max="100" placeholder="Min battery %" data-i18n-placeholder="minBatteryPlaceholder">
                <button class="secondary" id="applyFiltersBtn" data-i18n="applyFilters">Apply Filters</button>
            </div>
            <div class="message" id="bikeLoading" data-i18n="loadingBikes">Loading vehicles...</div>
            <div id="bicycleCards" class="bike-cards"></div>
            <div class="controls">
                <button class="secondary" id="bikePrevBtn" data-i18n="prevBikes">Prev</button>
                <span id="bikePageText">Page 1</span>
                <button class="secondary" id="bikeNextBtn" data-i18n="nextBikes">Next</button>
            </div>
            <button type="button" class="secondary station-back-btn" id="clearStationFocusBtn" data-i18n="clearStationPick">Back to station list</button>
        </div>
    </section>

    <section class="card dashboard-map-card">
        <h2 data-i18n="stationExplorer">Map &amp; stations</h2>
        <div class="message dashboard-hint" data-i18n="stationMsg">See where bikes and e-scooters are available around campus.</div>
        <div class="map-toolbar">
            <button type="button" class="secondary map-locate-btn" id="mapLocateMeBtn" data-i18n="mapLocateMe">My location</button>
            <button type="button" class="secondary map-locate-btn" id="mapRefreshGpsBtn" data-i18n="userLocRefreshGps">Refresh GPS</button>
            <button type="button" class="secondary map-locate-btn" id="userLocationAdjustMapBtn" data-i18n="userLocAdjustMap">Set location manually</button>
            <button type="button" class="secondary map-locate-btn is-hidden" id="userLocationResetGpsBtn" data-i18n="userLocResetGps">Use GPS again</button>
            <span id="mapNearestRentable" class="map-nearest-banner" aria-live="polite"></span>
        </div>
        <div id="mapGeoStatus" class="message muted map-geo-status" role="status"></div>
        <div id="userLocationConfirmBar" class="user-location-confirm-bar is-hidden" role="region" aria-label="Location confirmation">
            <p id="userLocationConfirmIntro" class="message user-location-confirm-intro"></p>
            <div class="user-location-confirm-actions">
                <button type="button" class="primary" id="userLocationConfirmUseBtn" data-i18n="userLocConfirmUse">Use this position</button>
                <button type="button" class="secondary" id="userLocationPickMapBtn" data-i18n="userLocPickOnMap">Adjust on map</button>
            </div>
        </div>
        <p id="userLocationPickHint" class="message muted map-geo-status is-hidden" role="status"></p>
        <p id="mapRelocateStaffHint" class="message muted map-relocate-hint is-hidden"></p>
        <div class="dashboard-map-table-split">
            <div class="dashboard-map-pane">
                <div id="stationMap"></div>
            </div>
            <div class="dashboard-stations-table-pane">
                <table class="dashboard-table dashboard-stations-table">
                    <thead>
                    <tr>
                        <th data-i18n="thStation">Station</th>
                        <th data-i18n="thCapacity">Parking spots</th>
                        <th data-i18n="thBicycles">Bikes available</th>
                        <th data-i18n="thScooters">E-scooters available</th>
                    </tr>
                    </thead>
                    <tbody id="stationBody"></tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="rentalSuccessCard" class="card success-card is-hidden" aria-live="polite">
        <div class="success-icon" aria-hidden="true">✓</div>
        <div>
            <h2 data-i18n="rentalSuccessTitle">Rental Successful</h2>
            <div id="rentalSuccessText" class="message" data-i18n="rentalSuccessHint">Your bike is unlocked and ready to ride.</div>
            <div id="rentalActiveVehicleText" class="message"></div>
            <div class="message" id="timerText"></div>
            <div class="message" id="rentalFeeText"></div>
            <p class="message muted dashboard-hint" id="rentalPolicyHint" hidden></p>
            <p class="message rental-overdue-banner is-hidden" id="rentalOvertimeWarn" role="alert"></p>
        </div>
    </section>

    <section class="card return-ride-card" id="returnRideSection" aria-labelledby="returnBlockHeading">
        <h2 id="returnBlockHeading" data-i18n="returnBlockTitle">Return your ride</h2>
        <p id="returnNoRideMsg" class="message muted" data-i18n="returnNoRideHint">When you have an active trip, you can end it here by choosing a return station.</p>
        <div id="returnRideForm" class="return-ride-form is-hidden">
            <p id="returnActiveTripMeta" class="message muted" aria-live="polite"></p>
            <p class="message" data-i18n="returnBlockDesc">Select the station where you are docking the vehicle, then confirm return.</p>
            <div class="return-ride-controls">
                <div>
                    <label for="returnStationSelect" data-i18n="returnStationLabel">Return station</label>
                    <select id="returnStationSelect"></select>
                </div>
                <button type="button" id="returnRideBtn" data-i18n="returnBtn">Return</button>
            </div>
            <div id="returnAlternativesWrap" class="return-alternatives is-hidden" aria-live="polite">
                <p class="message" id="returnAlternativesHeading" data-i18n="returnAlternativesTitle">Stations with available parking</p>
                <ul id="returnAlternativesList" class="return-alternatives-list"></ul>
            </div>
        </div>
    </section>

    <section class="card">
        <h2 data-i18n="orderHistory">My trips</h2>
        <div class="message" id="orderLoading" data-i18n="loadingOrders">Loading your trips...</div>
        <table class="dashboard-table">
            <thead>
            <tr>
                <th data-i18n="thTripNo">Trip #</th>
                <th data-i18n="thVehicle">Vehicle</th>
                <th data-i18n="thBrand">Brand</th>
                <th data-i18n="thStartTime">Started</th>
                <th data-i18n="thEndTime">Ended</th>
                <th data-i18n="thDuration">Minutes</th>
                <th data-i18n="thFee">Fare</th>
                <th data-i18n="thStatus">Status</th>
            </tr>
            </thead>
            <tbody id="orderBody"></tbody>
        </table>
        <div class="controls">
            <button class="secondary" id="orderPrevBtn" data-i18n="prevOrders">Prev Orders</button>
            <span id="orderPageText">Page 1</span>
            <button class="secondary" id="orderNextBtn" data-i18n="nextOrders">Next Orders</button>
        </div>
    </section>
</main>
<div id="rentModal" class="modal-backdrop" aria-hidden="true">
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="rentModalTitle">
        <div class="modal-head">
            <h3 id="rentModalTitle" data-i18n="rentModalTitle">Confirm Your Rental</h3>
            <button class="secondary" id="closeRentModalBtn" data-i18n="closeBtn">Close</button>
        </div>
        <div id="rentModalSummary" class="message"></div>
        <div class="modal-detail-grid">
            <div><strong data-i18n="modalVehicleType">Type:</strong> <span id="modalVehicleTypeValue">-</span></div>
            <div><strong data-i18n="modalVehicleId">Vehicle ID:</strong> <span id="modalBikeIdValue">-</span></div>
            <div><strong data-i18n="modalSerial">Serial:</strong> <span id="modalSerialValue">-</span></div>
            <div><strong data-i18n="modalBrand">Brand:</strong> <span id="modalBrandValue">-</span></div>
            <div><strong data-i18n="modalStation">Station:</strong> <span id="modalStationValue">-</span></div>
            <div><strong data-i18n="modalBattery">Battery:</strong> <span id="modalBatteryValue">-</span></div>
            <div><strong data-i18n="modalPrice">Price:</strong> <span id="modalPriceValue">-</span></div>
        </div>
        <div class="controls">
            <button id="confirmRentModalBtn" data-i18n="confirmRentBtn">Confirm Rent</button>
        </div>
    </div>
</div>
<div id="rentSuccessModal" class="modal-backdrop success-modal-backdrop" aria-hidden="true">
    <div class="modal-panel success-modal-panel" role="dialog" aria-modal="true" aria-labelledby="rentSuccessModalTitle">
        <div class="modal-head">
            <h3 id="rentSuccessModalTitle" data-i18n="rentSuccessModalTitle">Rental Successful</h3>
            <button class="secondary" id="closeRentSuccessModalBtn" data-i18n="closeBtn">Close</button>
        </div>
        <div id="rentSuccessModalSummary" class="message" data-i18n="rentSuccessModalHint">Your rental has started. Here are the trip details.</div>
        <div class="modal-detail-grid">
            <div><strong data-i18n="modalOrderId">Order ID:</strong> <span id="successOrderIdValue">-</span></div>
            <div><strong data-i18n="modalStartTime">Start time:</strong> <span id="successStartTimeValue">-</span></div>
            <div><strong data-i18n="modalVehicleType">Type:</strong> <span id="successVehicleTypeValue">-</span></div>
            <div><strong data-i18n="modalVehicleId">Vehicle ID:</strong> <span id="successVehicleIdValue">-</span></div>
            <div><strong data-i18n="modalSerial">Serial:</strong> <span id="successSerialValue">-</span></div>
            <div><strong data-i18n="modalBrand">Brand:</strong> <span id="successBrandValue">-</span></div>
            <div><strong data-i18n="modalStation">Station:</strong> <span id="successStationValue">-</span></div>
            <div><strong data-i18n="modalPrice">Price:</strong> <span id="successPriceValue">-</span></div>
        </div>
        <div class="controls">
            <button id="ackRentSuccessBtn" data-i18n="rentSuccessAcknowledge">Got it</button>
        </div>
    </div>
</div>
<div id="returnConfirmModal" class="modal-backdrop" aria-hidden="true">
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="returnConfirmModalTitle">
        <div class="modal-head">
            <h3 id="returnConfirmModalTitle" data-i18n="returnConfirmModalTitle">Confirm Return</h3>
            <button class="secondary" id="closeReturnConfirmModalBtn" data-i18n="closeBtn">Close</button>
        </div>
        <div id="returnConfirmModalSummary" class="message" data-i18n="returnConfirmModalHint">Please confirm the return details before ending your ride.</div>
        <div class="modal-detail-grid">
            <div><strong data-i18n="modalOrderId">Order ID:</strong> <span id="returnModalOrderIdValue">-</span></div>
            <div><strong data-i18n="modalStartTime">Start time:</strong> <span id="returnModalStartTimeValue">-</span></div>
            <div><strong data-i18n="modalVehicleType">Type:</strong> <span id="returnModalVehicleTypeValue">-</span></div>
            <div><strong data-i18n="modalVehicleId">Vehicle ID:</strong> <span id="returnModalVehicleIdValue">-</span></div>
            <div><strong data-i18n="modalSerial">Serial:</strong> <span id="returnModalSerialValue">-</span></div>
            <div><strong data-i18n="modalBrand">Brand:</strong> <span id="returnModalBrandValue">-</span></div>
            <div><strong data-i18n="modalReturnStation">Return station:</strong> <span id="returnModalStationValue">-</span></div>
            <div><strong data-i18n="modalPrice">Price:</strong> <span id="returnModalPriceValue">-</span></div>
        </div>
        <div class="message success-metric-line">
            <span class="metric-label time" data-i18n="activeRentalTimeLabel">Rental time</span>
            <span class="metric-value" id="returnModalTimeValue">-</span>
        </div>
        <div class="message success-metric-line">
            <span class="metric-label fee" data-i18n="activeRentalFeeLabel">Current fare</span>
            <span class="metric-value" id="returnModalFeeValue">-</span>
        </div>
        <div class="controls">
            <button id="confirmReturnModalBtn" data-i18n="confirmReturnBtn">Confirm Return</button>
        </div>
    </div>
</div>

<div id="userPositionPickModal" class="modal-backdrop" aria-hidden="true">
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="userPositionPickModalTitle">
        <div class="modal-head">
            <h3 id="userPositionPickModalTitle" data-i18n="userPosPickTitle">Use this point as your position?</h3>
            <button type="button" class="secondary" id="closeUserPositionPickModalBtn" data-i18n="closeBtn">Close</button>
        </div>
        <p id="userPositionPickModalBody" class="message"></p>
        <div class="controls">
            <button type="button" class="secondary" id="cancelUserPositionPickBtn" data-i18n="closeBtn">Cancel</button>
            <button type="button" id="confirmUserPositionPickBtn" data-i18n="userPosPickConfirm">Confirm</button>
        </div>
    </div>
</div>

<div id="stationRelocateModal" class="modal-backdrop" aria-hidden="true">
    <div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="stationRelocateModalTitle">
        <div class="modal-head">
            <h3 id="stationRelocateModalTitle" data-i18n="mapRelocateTitle">Update station location</h3>
            <button type="button" class="secondary" id="closeStationRelocateModalBtn" data-i18n="closeBtn">Close</button>
        </div>
        <p id="stationRelocateModalBody" class="message"></p>
        <div class="modal-detail-grid">
            <div>
                <label for="stationRelocateSelect" data-i18n="mapRelocateStationLabel">Station</label>
                <select id="stationRelocateSelect" class="full-width-select"></select>
            </div>
            <div><strong data-i18n="mapRelocateCoordsLabel">New coordinates</strong> <span id="stationRelocateCoordsText">-</span></div>
        </div>
        <div class="controls">
            <button type="button" class="secondary" id="cancelStationRelocateBtn" data-i18n="closeBtn">Cancel</button>
            <button type="button" id="confirmStationRelocateBtn" data-i18n="mapRelocateConfirm">Save location</button>
        </div>
    </div>
</div>

<div id="walletModal" class="modal-backdrop wallet-modal-backdrop" aria-hidden="true">
    <div class="modal-panel wallet-modal-panel" role="dialog" aria-modal="true" aria-labelledby="walletModalTitle">
        <div class="wallet-modal-badge" aria-hidden="true">!</div>
        <div class="modal-head wallet-modal-head">
            <h3 id="walletModalTitle" data-i18n="walletModalTitle">Insufficient wallet balance</h3>
            <button type="button" class="secondary" id="closeWalletModalBtn" data-i18n="closeBtn">Close</button>
        </div>
        <p id="walletModalBody" class="message wallet-modal-body" data-i18n="walletModalBody">Your current balance is not enough to start a rental. Please top up first.</p>
        <div class="controls wallet-modal-actions">
            <button type="button" class="secondary" id="walletModalDismissBtn" data-i18n="walletModalDismiss">Maybe later</button>
            <button type="button" id="walletModalTopupBtn" data-i18n="walletModalTopup">Top up now</button>
        </div>
    </div>
</div>

<div id="activeOrderModal" class="modal-backdrop active-order-modal-backdrop" aria-hidden="true">
    <div class="modal-panel active-order-modal-panel" role="dialog" aria-modal="true" aria-labelledby="activeOrderModalTitle">
        <div class="active-order-modal-badge" aria-hidden="true">!</div>
        <div class="modal-head active-order-modal-head">
            <h3 id="activeOrderModalTitle" data-i18n="activeOrderModalTitle">You already have an active ride</h3>
            <button type="button" class="secondary" id="closeActiveOrderModalBtn" data-i18n="closeBtn">Close</button>
        </div>
        <p id="activeOrderModalBody" class="message active-order-modal-body" data-i18n="activeOrderModalBody">Finish your current trip before starting another rental.</p>
        <div class="controls active-order-modal-actions">
            <button type="button" class="secondary" id="activeOrderModalDismissBtn" data-i18n="activeOrderModalDismiss">Understood</button>
            <button type="button" id="activeOrderModalGoReturnBtn" data-i18n="activeOrderModalGoReturn">Go to return section</button>
        </div>
    </div>
</div>

<?php require $projectRoot . '/includes/public_footer.php'; ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
<script src="./assets/footer_i18n.js?v=<?= file_exists($projectRoot . '/assets/footer_i18n.js') ? filemtime($projectRoot . '/assets/footer_i18n.js') : time() ?>"></script>
<script>
let activeStartTime = null;
let timer = null;
let stationMap = null;
let stationLayer = null;
let userLocationLayer = null;
let userPickPreviewLayer = null;
let relocatePreviewLayer = null;
let geoWatchId = null;
let mapDidAutoFitStations = false;
const UM_BOUNDS = {
    south: 22.1150,
    west: 113.5250,
    north: 22.1455,
    east: 113.5600,
};
/** If the two closest rentable stops differ by less than this (km), keep the previous choice — avoids GPS jitter between nearby pins (e.g. N8 vs E11). ~18 m */
const NEAREST_RENTABLE_TIE_GAP_KM = 0.018;
const state = {
    bikePage: 1,
    orderPage: 1,
    bikesTotalPages: 1,
    ordersTotalPages: 1,
    stationsById: {},
    stationsByName: {},
    selectedPickupLat: null,
    selectedPickupLng: null,
    selectedBike: null,
    activeOrder: null,
    /** When set, bottom panel lists vehicles only for this station */
    focusStationId: null,
    /** Filled from get_dashboard.rentalPolicy (defaults match includes/rental_limits.php) */
    rentalPolicy: { maxRentalMinutes: 120, overtimePenaltyMop: 500 },
    /** Last stations payload used for map / nearest-station logic */
    mapStations: [],
    nearestRentableStationId: null,
    userGeoLat: null,
    userGeoLng: null,
    canEditStationCoordinates: false,
    pendingRelocate: null,
    /** GPS readings from browser (always updated while watching). */
    userGeoLatRaw: null,
    userGeoLngRaw: null,
    userGeoAccRaw: null,
    /** User confirmed using location on map / nearest-station logic */
    userGeoConfirmed: false,
    /** Manual correction from map tap (overrides display until "Use GPS again") */
    userCalibratedLat: null,
    userCalibratedLng: null,
    pendingUserPickLat: null,
    pendingUserPickLng: null,
    userGeoAdjustMode: false,
};
const i18n = mergeFooterI18n({
    en: {
        pageTitle: 'UM Rental · My rides',
        dashboardKicker: 'UM Rental', dashboardWelcome: 'Welcome back', dashboardSub: 'Choose where to start, pick an available bike or e-scooter, and track your trips — all in one place.',
        navBrand: 'UM Rental', navHome: 'Home', navFeatured: 'Services', navAbout: 'Why us', navHowto: 'How to use', navContact: 'Contact Us', cta: 'Login / Use Service',
        navAccount: 'My Account', navLogout: 'Logout', navMyRental: 'My rental', navUserDashboard: 'Rental home', navStaffHome: 'Staff portal', navStaffHomeShort: 'Staff', navAdminPortal: 'Admin portal',
        title: 'UM Rental · My rides', backStaffHome: 'Back to Staff Home', adminPortal: 'Admin Portal', myAccount: 'My Account', welcome: 'Welcome', logout: 'Logout',
        searchTitle: 'Find a ride', pickupLocationLabel: 'Pickup location', pickupTimeLabel: 'Pickup time', dropoffTimeLabel: 'Drop-off time', searchRideBtn: 'Search',         resultsTitle: 'Campus rental stations',
        stationPickIntro: 'Each campus stop lists bicycle and e-scooter availability (including zero). Open a stop to choose a vehicle.',
        stationResultsCount: '{count} stations',
        noStationsAvailable: 'No stations have rentable vehicles right now.',
        stationStatusOpen: 'Open',
        stationStatusOther: 'Limited',
        pickStationBtn: 'See vehicles here',
        vehiclesAtStationTitle: 'Vehicles at {name}',
        clearStationPick: 'Back to station list',
        stationStatBikes: 'Bikes',
        stationStatScooters: 'E-scooters',
        typeBicycle: 'Bike',
        typeScooter: 'E-scooter',
        modalVehicleType: 'Type:', modalVehicleId: 'Vehicle ID:',
        sortByLabel: 'Sort by', sortPriceAsc: 'Price: low to high', sortPriceDesc: 'Price: high to low', resultsCount: '{count} results',
        rentableTag: 'Rentable', stationAvailability: '{count} bikes at this station', distanceKm: '{distance} km from pickup',
        openRentModalBtn: 'Choose Bike & Rent', rentModalTitle: 'Confirm Your Rental', closeBtn: 'Close',
        modalSerial: 'Serial:', modalBrand: 'Brand:', modalStation: 'Station:', modalBattery: 'Battery:', modalPrice: 'Price:',
        confirmRentBtn: 'Confirm Rent', modalNoBike: 'Please choose a vehicle first.',
        rentSuccessModalTitle: 'Rental Successful',
        rentSuccessModalHint: 'Your rental has started. Keep this information for your trip.',
        rentSuccessAcknowledge: 'Got it',
        returnConfirmModalTitle: 'Confirm Return',
        returnConfirmModalHint: 'Please review your ride details before ending this trip.',
        confirmReturnBtn: 'Confirm Return',
        modalOrderId: 'Order ID:',
        modalStartTime: 'Start time:',
        modalReturnStation: 'Return station:',
        stationExplorer: 'Map & stations', stationMsg: 'See where bikes and e-scooters are available around campus.',
        mapLocateMe: 'My location',
        mapGeoPending: 'Getting your location…',
        mapGeoDenied: 'Could not access location. Allow location in your browser to see nearby stations.',
        mapGeoOutsideCampus: 'Your location is outside the campus map — markers hidden.',
        mapGeoNearestRentable: 'Nearest with vehicles: {name} · {distance} · Bikes {bikes} · E-scooters {scooters}',
        mapGeoNearestRentableSkipCloser: 'Closer stop {nearName} ({nearDist}) has no vehicles right now. Nearest with vehicles: {name} · {distance} · Bikes {bikes} · E-scooters {scooters}',
        mapGeoNearestNone: 'No station currently has rentable vehicles. Closest stop: {name} · {distance}',
        mapRelocateHintStaff: 'Staff: click the map to correct a station pin. Confirm to save new coordinates.',
        mapRelocateTitle: 'Update station location',
        mapRelocateStationLabel: 'Station',
        mapRelocateCoordsLabel: 'New coordinates:',
        mapRelocateConfirm: 'Save location',
        mapRelocateBody: 'Save map pin for "{name}" at {lat}, {lng}?',
        mapRelocateSuccess: 'Station location updated.',
        mapRelocateOutOfBounds: 'Please choose a point inside the campus map.',
        errMapOutOfBounds: 'Please choose a point inside the campus map.',
        errStationEditForbidden: 'Only staff can update station coordinates.',
        userLocConfirmIntro: 'Approximate location detected. Confirm to show your dot on the map, or adjust by tapping the map.',
        userLocConfirmUse: 'Use this position',
        userLocPickOnMap: 'Adjust on map',
        userLocResetGps: 'Use GPS again',
        userLocPickHint: 'Tap the campus map to place your position.',
        userPosPickTitle: 'Use this map point?',
        userPosPickBody: 'Set your displayed position to {lat}, {lng}?',
        userPosPickConfirm: 'Confirm',
        userPosSaved: 'Your position on the map has been updated.',
        userLocWaitConfirm: 'Please confirm or adjust your location first.',
        userLocRefreshGps: 'Refresh GPS',
        userLocAdjustMap: 'Set location manually',
        userLocGpsRefreshed: 'Location updated from GPS.',
        userLocGpsUpdatedManual: 'Your dot still follows your manual map position. Tap “Use GPS again” to follow satellite.',
        thStation: 'Station', thCapacity: 'Parking spots', thBicycles: 'Bikes available', thScooters: 'E-scooters available',
        thTripNo: 'Trip #', thVehicle: 'Vehicle', thBrand: 'Brand', thStartTime: 'Started', thEndTime: 'Ended', thDuration: 'Minutes', thFee: 'Fare', thStatus: 'Status',
        availableBikes: 'Available Bicycles', minBatteryPlaceholder: 'Min battery %', applyFilters: 'Apply Filters', loadingBikes: 'Loading vehicles...', prevBikes: 'Prev', nextBikes: 'Next',
        rentTitle: 'Rent', vehicleIdPlaceholder: 'Vehicle ID', rentBtn: 'Rent', returnTitle: 'Return', returnBtn: 'Return',
        rentalSuccessTitle: 'Rental Successful', rentalSuccessHint: 'Your bike is unlocked and ready to ride.',
        activeRentalVehicleLine: 'Vehicle: {type} · {brand} · {serial}',
        activeRentalTimerLine: 'Active rental time: {min}m {sec}s',
        activeRentalFeeLine: 'Current fare: MOP {fee}',
        activeRentalTimeLabel: 'Rental time',
        activeRentalFeeLabel: 'Current fare',
        rentalPolicyShort: 'Return within {hours} h. After the limit, end the trip here — a MOP {penalty} late fee is added to the metered rental charge.',
        rentalOvertimeWarning: 'Over the {hours} h limit — your total will include a MOP {penalty} penalty when you return.',
        orderHistory: 'My trips', loadingOrders: 'Loading your trips...', prevOrders: 'Prev Orders', nextOrders: 'Next Orders',
        allStations: 'All stations', allBrands: 'All brands', selectReturn: 'Select return station',
        noOrdersPage: 'No trips on this page yet.', noBikesFilter: 'No bicycles match current filters.',
        vehicleReady: 'Vehicle {id} selected. Click "Rent" in the form to confirm.',
        vehicleReadyToast: 'Vehicle {id} ready for rent',
        bikePage: 'Bike Page {page}/{total}', orderPage: 'Order Page {page}/{total}',
        searchSummary: 'Pickup: {station} | From: {pickup} | To: {dropoff}',
        rentalStarted: 'Rental started successfully', returnCompleted: 'Return completed',
        enterVehicleId: 'Please enter a valid vehicle ID.', chooseReturnStation: 'Please select return station.',
        stationCapacity: 'Capacity', stationBikes: 'Bikes', stationScooters: 'Scooters',
        errUserHasActiveOrder: 'You already have a ride in progress. Finish it before starting another.',
        errNoActiveOrder: 'No active ride to return.',
        errVehicleNotAvailable: 'That vehicle isn\'t available right now.',
        errReturnStationUnavailable: 'That return spot isn\'t available.',
        errStationFull: 'That station is full. Pick another place to return.',
        returnAlternativesTitle: 'Try one of these stations with free parking spots:',
        returnAltSelected: 'Return station set to {name}.',
        errRequestFailed: 'Something went wrong. Please try again.',
        errRentalNotAllowed: 'This account type cannot use the rental service.',
        errInsufficientBalance: 'Insufficient wallet balance.',
        errUserHasPendingPayment: 'You have an unpaid order. Please complete payment before renting again.',
        walletTopupRequired: 'Please top up your wallet before renting.',
        walletModalTitle: 'Insufficient wallet balance',
        walletModalBody: 'Your current balance is not enough to start a rental. Please top up first.',
        walletModalTopup: 'Go to wallet top-up',
        walletModalDismiss: 'Maybe later',
        activeOrderModalTitle: 'You already have an active ride',
        activeOrderModalBody: 'Finish your current trip before starting another rental.',
        activeOrderModalGoReturn: 'Go to return section',
        activeOrderModalDismiss: 'Understood',
        returnBlockTitle: 'Return your ride',
        returnBlockDesc: 'Select the station where you are docking the vehicle, then confirm return.',
        returnNoRideHint: 'You do not have an active trip. After you start a rental, you can end it here.',
        returnStationLabel: 'Return station',
        returnActiveTripLine: 'Trip {order} · Started {started}',
    },
    'zh-CN': {
        pageTitle: 'UM 租赁 · 我的行程',
        dashboardKicker: 'UM 租赁', dashboardWelcome: '欢迎回来', dashboardSub: '在这里选择起点、挑选可用的单车或电动滑板车，并查看你的行程记录。',
        navBrand: 'UM 租赁', navHome: '首页', navFeatured: '服务', navAbout: '核心价值', navHowto: '使用说明', navContact: '联系我们', cta: '立即登录 / 使用服务',
        navAccount: '我的账户', navLogout: '退出', navMyRental: '我的租借', navUserDashboard: '租借首页', navStaffHome: '员工入口', navStaffHomeShort: '员工', navAdminPortal: '管理入口',
        title: 'UM 租赁 · 我的行程', backStaffHome: '返回员工主页', adminPortal: '管理员后台', myAccount: '我的账户', welcome: '欢迎', logout: '登出',
        searchTitle: '找一辆车', pickupLocationLabel: '取车地点', pickupTimeLabel: '取车时间', dropoffTimeLabel: '还车时间', searchRideBtn: '搜索',         resultsTitle: '校园租借站点',
        stationPickIntro: '以下列出各校区站点的单车与电动滑板车可用数量（含零辆）；进入站点后再选择车辆。',
        stationResultsCount: '共 {count} 个站点',
        noStationsAvailable: '当前没有可租车辆的站点。',
        stationStatusOpen: '营运中',
        stationStatusOther: '有限服务',
        pickStationBtn: '查看该站车辆',
        vehiclesAtStationTitle: '{name} · 可选车辆',
        clearStationPick: '返回站点列表',
        stationStatBikes: '单车',
        stationStatScooters: '滑板车',
        typeBicycle: '单车',
        typeScooter: '电动滑板车',
        modalVehicleType: '类型：', modalVehicleId: '车辆 ID：',
        sortByLabel: '排序', sortPriceAsc: '价格：低到高', sortPriceDesc: '价格：高到低', resultsCount: '共 {count} 个结果',
        rentableTag: '可租', stationAvailability: '该站可租 {count} 辆', distanceKm: '距取车点 {distance} 公里',
        openRentModalBtn: '选择车辆并租借', rentModalTitle: '确认租借信息', closeBtn: '关闭',
        modalSerial: '编号：', modalBrand: '品牌：', modalStation: '站点：', modalBattery: '电量：', modalPrice: '价格：',
        confirmRentBtn: '确认租借', modalNoBike: '请先选择一辆车辆。',
        rentSuccessModalTitle: '租借成功',
        rentSuccessModalHint: '你的租借已开始，请确认以下行程信息。',
        rentSuccessAcknowledge: '我知道了',
        returnConfirmModalTitle: '确认归还信息',
        returnConfirmModalHint: '结束行程前，请先确认以下归还信息。',
        confirmReturnBtn: '确认归还',
        modalOrderId: '行程编号：',
        modalStartTime: '开始时间：',
        modalReturnStation: '归还站点：',
        stationExplorer: '地图与站点', stationMsg: '查看校园周边单车与电动滑板车的实时可用情况。',
        mapLocateMe: '定位我的位置',
        mapGeoPending: '正在获取您的位置…',
        mapGeoDenied: '无法获取位置，请在浏览器中允许定位以显示附近站点。',
        mapGeoOutsideCampus: '您的位置在校园地图范围外。',
        mapGeoNearestRentable: '最近可租借：{name} · {distance} · 单车 {bikes} · 滑板车 {scooters}',
        mapGeoNearestRentableSkipCloser: '距您更近的 {nearName}（{nearDist}）当前无可租车辆。最近可租借：{name} · {distance} · 单车 {bikes} · 滑板车 {scooters}',
        mapGeoNearestNone: '当前各站暂无可租车辆。最近站点：{name} · {distance}',
        mapRelocateHintStaff: '职员：点击地图可校正站点坐标，确认后保存到数据库。',
        mapRelocateTitle: '更新站点坐标',
        mapRelocateStationLabel: '站点',
        mapRelocateCoordsLabel: '新坐标：',
        mapRelocateConfirm: '保存位置',
        mapRelocateBody: '将「{name}」保存为地图上的 {lat}, {lng}？',
        mapRelocateSuccess: '站点位置已更新。',
        mapRelocateOutOfBounds: '请在校园地图范围内选择位置。',
        errMapOutOfBounds: '请在校园地图范围内选择位置。',
        errStationEditForbidden: '仅职员可修改站点坐标。',
        userLocConfirmIntro: '已检测到大致位置。确认后在地图上显示蓝点，或点击地图手动校正。',
        userLocConfirmUse: '使用此位置',
        userLocPickOnMap: '在地图上校正',
        userLocResetGps: '恢复卫星定位',
        userLocPickHint: '请在校园地图上点击以放置您的位置。',
        userPosPickTitle: '使用此地图位置？',
        userPosPickBody: '将您的显示位置设为 {lat}, {lng}？',
        userPosPickConfirm: '确认',
        userPosSaved: '地图上的位置已更新。',
        userLocWaitConfirm: '请先确认或校正您的定位。',
        userLocRefreshGps: '更新卫星定位',
        userLocAdjustMap: '手动设置位置',
        userLocGpsRefreshed: '已用最新卫星定位更新。',
        userLocGpsUpdatedManual: '当前为地图手动位置，蓝点未改。若要恢复卫星定位请点「恢复卫星定位」。',
        thStation: '站点', thCapacity: '车位', thBicycles: '可用单车', thScooters: '可用滑板车',
        thTripNo: '行程编号', thVehicle: '车辆', thBrand: '品牌', thStartTime: '开始', thEndTime: '结束', thDuration: '分钟', thFee: '费用', thStatus: '状态',
        availableBikes: '可租自行车', minBatteryPlaceholder: '最低电量 %', applyFilters: '套用筛选', loadingBikes: '正在加载车辆...', prevBikes: '上一页', nextBikes: '下一页',
        rentTitle: '租借', vehicleIdPlaceholder: '车辆 ID', rentBtn: '租借', returnTitle: '归还', returnBtn: '归还',
        rentalSuccessTitle: '租借成功', rentalSuccessHint: '车辆已解锁，可以开始骑行。',
        activeRentalVehicleLine: '车辆：{type} · {brand} · {serial}',
        activeRentalTimerLine: '已租借时间：{min}分 {sec}秒',
        activeRentalFeeLine: '当前费用：MOP {fee}',
        activeRentalTimeLabel: '已租借时间',
        activeRentalFeeLabel: '当前费用',
        rentalPolicyShort: '请在 {hours} 小时内归还。逾时须在此结束行程，除计时租金外另加 MOP {penalty} 罚金。',
        rentalOvertimeWarning: '已超过 {hours} 小时上限——归还时总费用将含 MOP {penalty} 罚金。',
        orderHistory: '我的行程', loadingOrders: '正在加载行程...', prevOrders: '上一页', nextOrders: '下一页',
        allStations: '全部站点', allBrands: '全部品牌', selectReturn: '选择归还站点',
        noOrdersPage: '这一页还没有行程记录。', noBikesFilter: '没有符合筛选条件的自行车。',
        vehicleReady: '车辆 {id} 已选中，请点击“租借”确认。', vehicleReadyToast: '车辆 {id} 已填入租借表单',
        bikePage: '自行车页 {page}/{total}', orderPage: '订单页 {page}/{total}',
        searchSummary: '取车点：{station}｜从：{pickup}｜到：{dropoff}',
        rentalStarted: '租借成功', returnCompleted: '归还完成',
        enterVehicleId: '请输入有效车辆 ID。', chooseReturnStation: '请选择归还站点。',
        stationCapacity: '容量', stationBikes: '单车', stationScooters: '滑板车',
        errUserHasActiveOrder: '你已有一笔进行中的行程，请先结束后再租借。',
        errNoActiveOrder: '当前没有可归还的行程。',
        errVehicleNotAvailable: '该车辆暂时不可租借。',
        errReturnStationUnavailable: '该归还点暂时不可用。',
        errStationFull: '归还点已满，请选择其他站点。',
        returnAlternativesTitle: '以下站点仍有空位，可尝试归还：',
        returnAltSelected: '已选择归还站点：{name}。',
        errRequestFailed: '操作未完成，请稍后再试。',
        errRentalNotAllowed: '该账户类型不可使用租借服务。',
        errInsufficientBalance: '钱包余额不足。',
        errUserHasPendingPayment: '你有未付款订单，请先完成付款后再租车。',
        walletTopupRequired: '请充值，银包没钱。',
        walletModalTitle: '钱包余额不足',
        walletModalBody: '当前余额不足以开始租借，请先充值。',
        walletModalTopup: '前往钱包充值',
        walletModalDismiss: '稍后再说',
        activeOrderModalTitle: '你已有进行中的行程',
        activeOrderModalBody: '请先归还当前车辆，再开始新的租借。',
        activeOrderModalGoReturn: '前往归还车辆',
        activeOrderModalDismiss: '知道了',
        returnBlockTitle: '归还车辆',
        returnBlockDesc: '请选择实际停放车辆的站点，然后确认归还。',
        returnNoRideHint: '当前没有进行中的行程。开始租借后可在此选择归还站点并结束行程。',
        returnStationLabel: '归还站点',
        returnActiveTripLine: '行程 {order} · 开始时间 {started}',
    },
    'zh-TW': {
        pageTitle: 'UM 租賃 · 我的行程',
        dashboardKicker: 'UM 租賃', dashboardWelcome: '歡迎回來', dashboardSub: '在這裡選擇起點、挑選可用的單車或電動滑板車，並查看你的行程紀錄。',
        navBrand: 'UM 租賃', navHome: '首頁', navFeatured: '服務', navAbout: '核心價值', navHowto: '使用說明', navContact: '聯繫我們', cta: '立即登入 / 使用服務',
        navAccount: '我的帳戶', navLogout: '登出', navMyRental: '我的租借', navUserDashboard: '租借首頁', navStaffHome: '員工入口', navStaffHomeShort: '員工', navAdminPortal: '管理入口',
        title: 'UM 租賃 · 我的行程', backStaffHome: '返回職員主頁', adminPortal: '管理後台', myAccount: '我的帳戶', welcome: '歡迎', logout: '登出',
        searchTitle: '找一輛車', pickupLocationLabel: '取車地點', pickupTimeLabel: '取車時間', dropoffTimeLabel: '還車時間', searchRideBtn: '搜尋',         resultsTitle: '校園租借站點',
        stationPickIntro: '以下列出各校區站點的單車與電動滑板車可用數量（含零輛）；進入站點後再選擇車輛。',
        stationResultsCount: '共 {count} 個站點',
        noStationsAvailable: '目前沒有可租車輛的站點。',
        stationStatusOpen: '營運中',
        stationStatusOther: '有限服務',
        pickStationBtn: '查看該站車輛',
        vehiclesAtStationTitle: '{name} · 可選車輛',
        clearStationPick: '返回站點列表',
        stationStatBikes: '單車',
        stationStatScooters: '滑板車',
        typeBicycle: '單車',
        typeScooter: '電動滑板車',
        modalVehicleType: '類型：', modalVehicleId: '車輛 ID：',
        sortByLabel: '排序', sortPriceAsc: '價格：低到高', sortPriceDesc: '價格：高到低', resultsCount: '共 {count} 個結果',
        rentableTag: '可租', stationAvailability: '該站可租 {count} 輛', distanceKm: '距取車點 {distance} 公里',
        openRentModalBtn: '選擇車輛並租借', rentModalTitle: '確認租借資訊', closeBtn: '關閉',
        modalSerial: '編號：', modalBrand: '品牌：', modalStation: '站點：', modalBattery: '電量：', modalPrice: '價格：',
        confirmRentBtn: '確認租借', modalNoBike: '請先選擇一輛車輛。',
        rentSuccessModalTitle: '租借成功',
        rentSuccessModalHint: '你的租借已開始，請確認以下行程資訊。',
        rentSuccessAcknowledge: '我知道了',
        returnConfirmModalTitle: '確認歸還資訊',
        returnConfirmModalHint: '結束行程前，請先確認以下歸還資訊。',
        confirmReturnBtn: '確認歸還',
        modalOrderId: '行程編號：',
        modalStartTime: '開始時間：',
        modalReturnStation: '歸還站點：',
        stationExplorer: '地圖與站點', stationMsg: '查看校園周邊單車與電動滑板車的即時可用情況。',
        mapLocateMe: '定位我的位置',
        mapGeoPending: '正在取得您的位置…',
        mapGeoDenied: '無法取得位置，請在瀏覽器中允許定位以顯示附近站點。',
        mapGeoOutsideCampus: '您的位置在校園地圖範圍外。',
        mapGeoNearestRentable: '最近可租借：{name} · {distance} · 單車 {bikes} · 滑板車 {scooters}',
        mapGeoNearestRentableSkipCloser: '距您較近的 {nearName}（{nearDist}）目前無可租車輛。最近可租借：{name} · {distance} · 單車 {bikes} · 滑板車 {scooters}',
        mapGeoNearestNone: '目前各站暫無可租車輛。最近站點：{name} · {distance}',
        mapRelocateHintStaff: '職員：點擊地圖可校正站點座標，確認後儲存至資料庫。',
        mapRelocateTitle: '更新站點座標',
        mapRelocateStationLabel: '站點',
        mapRelocateCoordsLabel: '新座標：',
        mapRelocateConfirm: '儲存位置',
        mapRelocateBody: '將「{name}」儲存為地圖上的 {lat}, {lng}？',
        mapRelocateSuccess: '站點位置已更新。',
        mapRelocateOutOfBounds: '請在校園地圖範圍內選擇位置。',
        errMapOutOfBounds: '請在校園地圖範圍內選擇位置。',
        errStationEditForbidden: '僅職員可修改站點座標。',
        userLocConfirmIntro: '已偵測到大約位置。確認後在地圖上顯示藍點，或點擊地圖手動校正。',
        userLocConfirmUse: '使用此位置',
        userLocPickOnMap: '在地圖上校正',
        userLocResetGps: '恢復衛星定位',
        userLocPickHint: '請在校園地圖上點選以放置您的位置。',
        userPosPickTitle: '使用此地圖位置？',
        userPosPickBody: '將您的顯示位置設為 {lat}, {lng}？',
        userPosPickConfirm: '確認',
        userPosSaved: '地圖上的位置已更新。',
        userLocWaitConfirm: '請先確認或校正您的定位。',
        userLocRefreshGps: '更新衛星定位',
        userLocAdjustMap: '手動設定位置',
        userLocGpsRefreshed: '已以最新衛星定位更新。',
        userLocGpsUpdatedManual: '目前為地圖手動位置，顯示點未變。若要改回跟隨衛星請按「恢復衛星定位」。',
        thStation: '站點', thCapacity: '車位', thBicycles: '可用單車', thScooters: '可用滑板車',
        thTripNo: '行程編號', thVehicle: '車輛', thBrand: '品牌', thStartTime: '開始', thEndTime: '結束', thDuration: '分鐘', thFee: '費用', thStatus: '狀態',
        availableBikes: '可租自行車', minBatteryPlaceholder: '最低電量 %', applyFilters: '套用篩選', loadingBikes: '正在載入車輛...', prevBikes: '上一頁', nextBikes: '下一頁',
        rentTitle: '租借', vehicleIdPlaceholder: '車輛 ID', rentBtn: '租借', returnTitle: '歸還', returnBtn: '歸還',
        rentalSuccessTitle: '租借成功', rentalSuccessHint: '車輛已解鎖，可以開始騎行。',
        activeRentalVehicleLine: '車輛：{type} · {brand} · {serial}',
        activeRentalTimerLine: '已租借時間：{min}分 {sec}秒',
        activeRentalFeeLine: '目前費用：MOP {fee}',
        activeRentalTimeLabel: '已租借時間',
        activeRentalFeeLabel: '目前費用',
        rentalPolicyShort: '請在 {hours} 小時內歸還。逾時須在此結束行程，除計時租金外另加 MOP {penalty} 罰金。',
        rentalOvertimeWarning: '已超過 {hours} 小時上限——歸還時總費用將含 MOP {penalty} 罰金。',
        orderHistory: '我的行程', loadingOrders: '正在載入行程...', prevOrders: '上一頁', nextOrders: '下一頁',
        allStations: '全部站點', allBrands: '全部品牌', selectReturn: '選擇歸還站點',
        noOrdersPage: '這一頁還沒有行程紀錄。', noBikesFilter: '沒有符合篩選條件的自行車。',
        vehicleReady: '車輛 {id} 已選中，請點擊「租借」確認。', vehicleReadyToast: '車輛 {id} 已填入租借表單',
        bikePage: '自行車頁 {page}/{total}', orderPage: '訂單頁 {page}/{total}',
        searchSummary: '取車點：{station}｜從：{pickup}｜到：{dropoff}',
        rentalStarted: '租借成功', returnCompleted: '歸還完成',
        enterVehicleId: '請輸入有效車輛 ID。', chooseReturnStation: '請選擇歸還站點。',
        stationCapacity: '容量', stationBikes: '單車', stationScooters: '滑板車',
        errUserHasActiveOrder: '你已有一筆進行中的行程，請先結束後再租借。',
        errNoActiveOrder: '目前沒有可歸還的行程。',
        errVehicleNotAvailable: '該車輛暫時不可租借。',
        errReturnStationUnavailable: '該歸還點暫時不可用。',
        errStationFull: '歸還點已滿，請選擇其他站點。',
        returnAlternativesTitle: '以下站點尚有空位，可嘗試歸還：',
        returnAltSelected: '已選擇歸還站點：{name}。',
        errRequestFailed: '操作未完成，請稍後再試。',
        errRentalNotAllowed: '此帳戶類型無法使用租借服務。',
        errInsufficientBalance: '錢包餘額不足。',
        errUserHasPendingPayment: '你有未付款訂單，請先完成付款後再租車。',
        walletTopupRequired: '請充值，銀包沒錢。',
        walletModalTitle: '錢包餘額不足',
        walletModalBody: '目前餘額不足以開始租借，請先儲值。',
        walletModalTopup: '前往錢包儲值',
        walletModalDismiss: '稍後再說',
        activeOrderModalTitle: '你已有進行中的行程',
        activeOrderModalBody: '請先歸還目前車輛，再開始新的租借。',
        activeOrderModalGoReturn: '前往歸還車輛',
        activeOrderModalDismiss: '知道了',
        returnBlockTitle: '歸還車輛',
        returnBlockDesc: '請選擇實際停放車輛的站點，然後確認歸還。',
        returnNoRideHint: '目前沒有進行中的行程。開始租借後可在此選擇歸還站點並結束行程。',
        returnStationLabel: '歸還站點',
        returnActiveTripLine: '行程 {order} · 開始時間 {started}',
    }
});
let lang = localStorage.getItem('lang') || 'en';
function t(key, vars = {}) {
    let str = (i18n[lang] && i18n[lang][key]) || i18n.en[key] || key;
    Object.keys(vars).forEach((k) => { str = str.replace(`{${k}}`, String(vars[k])); });
    return str;
}
/** True when localized label was stored with wrong MySQL charset (shows as question marks). */
function stationLocalizedLabelLooksCorrupt(s) {
    const t = String(s || '').trim();
    if (!t) return true;
    const withoutParenCode = t.replace(/\s*\([^)]{1,16}\)\s*$/, '').trim();
    if (!withoutParenCode) return false;
    return /^[\uFFFD\?]+$/.test(withoutParenCode);
}

/** Localized station label (API: name + name_zh_cn + name_zh_tw, or vehicle join: station_name + *_zh_*). */
function stationDisplayName(obj) {
    if (!obj) return '-';
    const zhCn = obj.name_zh_cn ?? obj.station_name_zh_cn;
    const zhTw = obj.name_zh_tw ?? obj.station_name_zh_tw;
    const en = obj.name ?? obj.station_name;
    if (lang === 'zh-CN' && zhCn && !stationLocalizedLabelLooksCorrupt(zhCn)) return String(zhCn);
    if (lang === 'zh-TW' && zhTw && !stationLocalizedLabelLooksCorrupt(zhTw)) return String(zhTw);
    return String(en || '-');
}
function applyLanguage() {
    document.documentElement.lang = lang;
    document.title = t('pageTitle');
    document.querySelectorAll('[data-i18n]').forEach((el) => { el.textContent = t(el.dataset.i18n); });
    document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => { el.placeholder = t(el.dataset.i18nPlaceholder); });
    const confirmBar = document.getElementById('userLocationConfirmBar');
    if (confirmBar && !confirmBar.classList.contains('is-hidden')
        && state.pendingUserConfirmLat != null && state.pendingUserConfirmLng != null) {
        const intro = document.getElementById('userLocationConfirmIntro');
        const acc = state.pendingUserConfirmAcc != null ? Number(state.pendingUserConfirmAcc) : 30;
        if (intro) {
            intro.textContent = `${t('userLocConfirmIntro')} (${state.pendingUserConfirmLat.toFixed(5)}, ${state.pendingUserConfirmLng.toFixed(5)} · ±${Math.round(acc)}m)`;
        }
    }
    if (state.userGeoAdjustMode) {
        const hint = document.getElementById('userLocationPickHint');
        if (hint) hint.textContent = t('userLocPickHint');
    }
    if (state.pendingUserPickLat != null && state.pendingUserPickLng != null) {
        const body = document.getElementById('userPositionPickModalBody');
        if (body) {
            body.textContent = t('userPosPickBody', {
                lat: state.pendingUserPickLat.toFixed(7),
                lng: state.pendingUserPickLng.toFixed(7),
            });
        }
    }
}

let miniPopupTimer = null;
function showMiniPopup(message, isError = false, options = {}) {
    const el = document.getElementById('miniPopup');
    if (!el) return;
    const durationMs = Number.isFinite(options.durationMs) ? Math.max(1200, Number(options.durationMs)) : 2600;
    el.textContent = message;
    el.classList.toggle('error', Boolean(isError));
    el.classList.toggle('wallet-warning', Boolean(options.walletWarning));
    el.classList.add('show');
    if (miniPopupTimer) clearTimeout(miniPopupTimer);
    miniPopupTimer = setTimeout(() => {
        el.classList.remove('show');
        el.classList.remove('error');
        el.classList.remove('wallet-warning');
    }, durationMs);
}

function mapApiMessageToI18nKey(message) {
    const raw = String(message || '').trim().toLowerCase();
    if (raw.includes('already has active order')) return 'errUserHasActiveOrder';
    if (raw.includes('payment pending order') || raw.includes('待付款訂單') || raw.includes('未付款訂單')) return 'errUserHasPendingPayment';
    if (raw.includes('no active order found')) return 'errNoActiveOrder';
    if (raw.includes('vehicle is not available')) return 'errVehicleNotAvailable';
    if (raw.includes('return station is unavailable')) return 'errReturnStationUnavailable';
    if (raw.includes('station is full')) return 'errStationFull';
    if (raw.includes('request failed')) return 'errRequestFailed';
    if (raw.includes('cannot use the rental') || raw.includes('無法使用租借')) return 'errRentalNotAllowed';
    if (raw.includes('insufficient wallet balance') || raw.includes('餘額不足')) return 'errInsufficientBalance';
    return '';
}

function showMappedPopup(message, isError = false, backendKey = '') {
    const key = backendKey || mapApiMessageToI18nKey(message);
    if (key && i18n.en[key]) {
        if (key === 'errInsufficientBalance') {
            showInsufficientBalanceModal();
            return;
        }
        if (key === 'errUserHasActiveOrder') {
            showActiveOrderBlockedModal();
            return;
        }
        showMiniPopup(t(key), isError);
        return;
    }
    showMiniPopup(message, isError);
}

function emphasizeReturnSection() {
    const section = document.getElementById('returnRideSection');
    if (!section) return;
    section.classList.remove('attention-highlight');
    // Force reflow to restart animation if user triggers repeatedly.
    // eslint-disable-next-line no-unused-expressions
    section.offsetWidth;
    section.classList.add('attention-highlight');
    section.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => {
        section.classList.remove('attention-highlight');
    }, 2600);
}

function hideReturnAlternatives() {
    const wrap = document.getElementById('returnAlternativesWrap');
    const list = document.getElementById('returnAlternativesList');
    if (wrap) wrap.classList.add('is-hidden');
    if (list) list.innerHTML = '';
}

function showReturnAlternatives(alts) {
    const wrap = document.getElementById('returnAlternativesWrap');
    const list = document.getElementById('returnAlternativesList');
    const sel = document.getElementById('returnStationSelect');
    if (!wrap || !list || !sel) return;
    list.innerHTML = '';
    if (!Array.isArray(alts) || alts.length === 0) {
        wrap.classList.add('is-hidden');
        return;
    }
    const heading = document.getElementById('returnAlternativesHeading');
    if (heading) heading.textContent = t('returnAlternativesTitle');
    alts.forEach((a) => {
        const id = String(a.id);
        const stAlt = state.stationsById[id];
        const name = stAlt ? stationDisplayName(stAlt) : String(a.name || '');
        const cap = a.capacity != null ? Number(a.capacity) : NaN;
        const occ = a.occupiedSlots != null ? Number(a.occupiedSlots) : NaN;
        const free = Number.isFinite(cap) && Number.isFinite(occ) ? Math.max(0, cap - occ) : null;
        const li = document.createElement('li');
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'return-alt-btn';
        btn.setAttribute('aria-label', name);
        btn.textContent = free != null ? `${name} (${free})` : name;
        btn.addEventListener('click', () => {
            if ([...sel.options].some((o) => o.value === id)) {
                sel.value = id;
            }
            showMiniPopup(t('returnAltSelected', { name }), false);
        });
        li.appendChild(btn);
        list.appendChild(li);
    });
    wrap.classList.remove('is-hidden');
}

async function api(action, method = 'GET', payload = null, query = null) {
    const url = new URL('./api/dashboard_api.php', window.location.href);
    url.searchParams.set('action', action);
    if (query) {
        Object.entries(query).forEach(([k, v]) => {
            if (v !== null && v !== undefined && String(v) !== '') {
                url.searchParams.set(k, String(v));
            }
        });
    }
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const options = { method, headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrfToken } };
    if (payload) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(payload);
    }
    const res = await fetch(url, options);
    const data = await res.json();
    if (!data.success) {
        const err = new Error(data.message || 'Request failed');
        err.i18nKey = data.message_key || '';
        err.code = data.code || '';
        const alt = data.data && Array.isArray(data.data.alternatives) ? data.data.alternatives : [];
        err.alternatives = alt;
        throw err;
    }
    return data;
}

function renderStations(stations) {
    state.mapStations = Array.isArray(stations) ? stations : [];
    const body = document.getElementById('stationBody');
    const select = document.getElementById('returnStationSelect');
    const filterStation = document.getElementById('filterStation');
    const pickupLocation = document.getElementById('pickupLocation');
    body.innerHTML = '';
    if (select) select.innerHTML = `<option value="">${t('selectReturn')}</option>`;
    filterStation.innerHTML = `<option value="">${t('allStations')}</option>`;
    pickupLocation.innerHTML = `<option value="">${t('allStations')}</option>`;

    state.stationsById = {};
    state.stationsByName = {};
    stations.forEach((s) => {
        state.stationsById[String(s.id)] = s;
        state.stationsByName[String(s.name || '')] = s;
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${stationDisplayName(s)}</td>
          <td>${s.capacity}</td>
          <td>${s.available_bicycles}</td>
          <td>${s.available_scooters}</td>
        `;
        body.appendChild(tr);

        const option = document.createElement('option');
        option.value = s.id;
        option.textContent = stationDisplayName(s);
        if (select) select.appendChild(option);

        const filterOption = document.createElement('option');
        filterOption.value = s.id;
        filterOption.textContent = stationDisplayName(s);
        filterStation.appendChild(filterOption);

        const pickupOption = document.createElement('option');
        pickupOption.value = s.id;
        pickupOption.textContent = stationDisplayName(s);
        pickupLocation.appendChild(pickupOption);
    });
    if (state.focusStationId && state.stationsById[String(state.focusStationId)]) {
        filterStation.value = String(state.focusStationId);
    }
    if (state.userGeoConfirmed && state.userGeoLat != null && state.userGeoLng != null) {
        updateNearestRentableBanner();
    }
    renderStationMap(stations);
    updateUserLocationToolbarExtras();
}

function escapeHtml(str) {
    const el = document.createElement('div');
    el.textContent = String(str ?? '');
    return el.innerHTML;
}

/** Extract building/stop code from canonical English names, e.g. "(E2)" → "E2". */
function stationBuildingCode(name) {
    const m = String(name || '').match(/\(([^)]+)\)\s*$/);
    return m ? m[1].trim() : '';
}

function umLatLngOk(lat, lng) {
    return lat >= UM_BOUNDS.south && lat <= UM_BOUNDS.north && lng >= UM_BOUNDS.west && lng <= UM_BOUNDS.east;
}

function normalizeStationLatLng(s) {
    if (s.latitude == null || s.longitude == null) return null;
    let lat = Number(s.latitude);
    let lng = Number(s.longitude);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
    // Swapped WGS84 columns (e.g. ~113 stored as "latitude") — common import bug.
    if (Math.abs(lat) > 90) {
        if (Math.abs(lng) <= 90 && Math.abs(lat) <= 180) {
            const tmp = lat;
            lat = lng;
            lng = tmp;
        } else {
            return null;
        }
    }
    if (!umLatLngOk(lat, lng) && umLatLngOk(lng, lat)) {
        const tmp = lat;
        lat = lng;
        lng = tmp;
    }
    if (!umLatLngOk(lat, lng)) return null;
    return { lat, lng };
}

function drawUserLocation(lat, lng, accuracyM) {
    if (!stationMap || !userLocationLayer) return;
    userLocationLayer.clearLayers();
    const pulsingIcon = L.divIcon({
        className: 'user-location-marker-wrap',
        html: '<div class="user-location-marker" aria-hidden="true"><span class="user-location-core"></span><span class="user-location-ring"></span></div>',
        iconSize: [28, 28],
        iconAnchor: [14, 14],
    });
    const m = L.marker([lat, lng], { icon: pulsingIcon, zIndexOffset: 800 });
    m.bindTooltip(t('mapLocateMe'), { direction: 'top' });
    m.addTo(userLocationLayer);
    const acc = Number(accuracyM);
    if (Number.isFinite(acc) && acc > 5 && acc < 400) {
        L.circle([lat, lng], {
            radius: acc,
            color: '#3b82f6',
            weight: 1,
            fillOpacity: 0.06,
        }).addTo(userLocationLayer);
    }
}

function updateUserLocationToolbarExtras() {
    const resetBtn = document.getElementById('userLocationResetGpsBtn');
    const adjustBtn = document.getElementById('userLocationAdjustMapBtn');
    if (resetBtn) resetBtn.classList.toggle('is-hidden', state.userCalibratedLat == null);
    if (adjustBtn) adjustBtn.classList.remove('is-hidden');
}

function hideUserLocationConfirmBar() {
    const bar = document.getElementById('userLocationConfirmBar');
    if (bar) bar.classList.add('is-hidden');
}

function showUserLocationConfirmBar(lat, lng, acc) {
    state.pendingUserConfirmLat = lat;
    state.pendingUserConfirmLng = lng;
    state.pendingUserConfirmAcc = acc;
    const bar = document.getElementById('userLocationConfirmBar');
    const intro = document.getElementById('userLocationConfirmIntro');
    if (intro) {
        intro.textContent = `${t('userLocConfirmIntro')} (${lat.toFixed(5)}, ${lng.toFixed(5)} · ±${Math.round(acc)}m)`;
    }
    if (bar) bar.classList.remove('is-hidden');
}

function applyUserDisplayedLocation() {
    if (!state.userGeoConfirmed) return;
    const lat = state.userCalibratedLat ?? state.userGeoLatRaw;
    const lng = state.userCalibratedLng ?? state.userGeoLngRaw;
    if (lat == null || lng == null) return;
    state.userGeoLat = lat;
    state.userGeoLng = lng;
    const accForCircle = state.userCalibratedLat != null ? null : state.userGeoAccRaw;
    drawUserLocation(lat, lng, accForCircle);
    const prevNearest = state.nearestRentableStationId;
    updateNearestRentableBanner();
    if (String(prevNearest) !== String(state.nearestRentableStationId) && state.mapStations.length) {
        renderStationMap(state.mapStations);
    }
    updateUserLocationToolbarExtras();
}

function formatDistanceForBanner(km) {
    if (km == null || !Number.isFinite(km)) return '';
    if (km < 1) return `${Math.round(km * 1000)} m`;
    return `${km.toFixed(2)} km`;
}

function refreshUserGpsOnce() {
    if (!navigator.geolocation) {
        showMiniPopup(t('mapGeoDenied'), true);
        return;
    }
    const statusEl = document.getElementById('mapGeoStatus');
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            const acc = pos.coords.accuracy;
            state.userGeoLatRaw = lat;
            state.userGeoLngRaw = lng;
            state.userGeoAccRaw = acc;
            if (!umLatLngOk(lat, lng)) {
                if (statusEl) statusEl.textContent = t('mapGeoOutsideCampus');
                showMiniPopup(t('mapGeoOutsideCampus'), true);
                return;
            }
            if (statusEl) statusEl.textContent = '';
            if (!state.userGeoConfirmed) {
                showUserLocationConfirmBar(lat, lng, acc);
                showMiniPopup(t('userLocGpsRefreshed'), false);
                return;
            }
            if (state.userCalibratedLat != null && state.userCalibratedLng != null) {
                showMiniPopup(t('userLocGpsUpdatedManual'), false);
                return;
            }
            state.userGeoLat = lat;
            state.userGeoLng = lng;
            drawUserLocation(lat, lng, acc);
            const prevNearest = state.nearestRentableStationId;
            updateNearestRentableBanner();
            if (String(prevNearest) !== String(state.nearestRentableStationId) && state.mapStations.length) {
                renderStationMap(state.mapStations);
            }
            showMiniPopup(t('userLocGpsRefreshed'), false);
        },
        () => {
            if (statusEl) statusEl.textContent = t('mapGeoDenied');
            showMiniPopup(t('mapGeoDenied'), true);
        },
        { enableHighAccuracy: true, timeout: 18000, maximumAge: 0 },
    );
}

function exitUserGeoPickMode() {
    state.userGeoAdjustMode = false;
    const hint = document.getElementById('userLocationPickHint');
    if (hint) {
        hint.classList.add('is-hidden');
        hint.textContent = '';
    }
    if (userPickPreviewLayer) userPickPreviewLayer.clearLayers();
}

function enterUserGeoPickMode() {
    state.userGeoAdjustMode = true;
    const hint = document.getElementById('userLocationPickHint');
    if (hint) {
        hint.textContent = t('userLocPickHint');
        hint.classList.remove('is-hidden');
    }
    showMiniPopup(t('userLocPickHint'), false);
}

function openUserPositionPickModal(lat, lng) {
    const modal = document.getElementById('userPositionPickModal');
    const body = document.getElementById('userPositionPickModalBody');
    state.pendingUserPickLat = lat;
    state.pendingUserPickLng = lng;
    if (body) {
        body.textContent = t('userPosPickBody', { lat: lat.toFixed(7), lng: lng.toFixed(7) });
    }
    if (modal) {
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
    }
}

function cancelUserPositionPickFlow() {
    closeUserPositionPickModal();
    exitUserGeoPickMode();
    if (!state.userGeoConfirmed && state.userGeoLatRaw != null && umLatLngOk(state.userGeoLatRaw, state.userGeoLngRaw)) {
        showUserLocationConfirmBar(state.userGeoLatRaw, state.userGeoLngRaw, state.userGeoAccRaw || 30);
    }
}

function closeUserPositionPickModal() {
    const modal = document.getElementById('userPositionPickModal');
    if (modal) {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
    }
    if (userPickPreviewLayer) userPickPreviewLayer.clearLayers();
    state.pendingUserPickLat = null;
    state.pendingUserPickLng = null;
}

function handleUserPositionMapPick(lat, lng) {
    if (!umLatLngOk(lat, lng)) {
        showMiniPopup(t('mapRelocateOutOfBounds'), true);
        return;
    }
    if (userPickPreviewLayer) {
        userPickPreviewLayer.clearLayers();
        L.marker([lat, lng], {
            icon: L.divIcon({
                className: 'user-pick-preview-wrap',
                html: '<div class="user-pick-preview-dot"></div>',
                iconSize: [22, 22],
                iconAnchor: [11, 11],
            }),
        }).addTo(userPickPreviewLayer);
    }
    openUserPositionPickModal(lat, lng);
}

function stationHasRentableVehicles(s) {
    const bikes = Number(s.available_bicycles || 0);
    const sco = Number(s.available_scooters || 0);
    return bikes + sco > 0;
}

/** Closest rentable row; when #1 and #2 are almost tied, prefer previous station id to reduce flip-flop. */
function pickNearestRentableStable(withRentSorted, prevId) {
    if (!withRentSorted.length) return null;
    if (withRentSorted.length === 1) return withRentSorted[0];
    const first = withRentSorted[0];
    const second = withRentSorted[1];
    const gapKm = second.d - first.d;
    if (gapKm >= NEAREST_RENTABLE_TIE_GAP_KM) return first;
    const ps = prevId != null ? String(prevId) : '';
    if (ps === String(first.s.id)) return first;
    if (ps === String(second.s.id)) return second;
    return first;
}

function updateNearestRentableBanner() {
    const el = document.getElementById('mapNearestRentable');
    if (!el || !state.userGeoConfirmed || state.userGeoLat == null || state.userGeoLng == null) return;
    const stations = state.mapStations || [];
    const ulat = state.userGeoLat;
    const ulng = state.userGeoLng;
    const byDistance = stations
        .map((s) => {
            const pos = normalizeStationLatLng(s);
            if (!pos) return null;
            const d = calcDistanceKm(ulat, ulng, pos.lat, pos.lng);
            return { s, d };
        })
        .filter(Boolean)
        .sort((a, b) => a.d - b.d || String(a.s.id).localeCompare(String(b.s.id)));
    const withRent = byDistance.filter((row) => stationHasRentableVehicles(row.s));
    const bestRent = pickNearestRentableStable(withRent, state.nearestRentableStationId);
    const bestAny = byDistance[0] || null;
    if (bestRent) {
        state.nearestRentableStationId = bestRent.s.id;
        const geoClosest = byDistance[0];
        const skipCloserExplainsGap = geoClosest
            && String(geoClosest.s.id) !== String(bestRent.s.id)
            && !stationHasRentableVehicles(geoClosest.s);
        const rentPayload = {
            name: stationDisplayName(bestRent.s),
            distance: formatDistanceForBanner(bestRent.d),
            bikes: String(bestRent.s.available_bicycles ?? 0),
            scooters: String(bestRent.s.available_scooters ?? 0),
        };
        if (skipCloserExplainsGap) {
            el.textContent = t('mapGeoNearestRentableSkipCloser', {
                nearName: stationDisplayName(geoClosest.s),
                nearDist: formatDistanceForBanner(geoClosest.d),
                ...rentPayload,
            });
        } else {
            el.textContent = t('mapGeoNearestRentable', rentPayload);
        }
        el.classList.remove('is-muted');
    } else if (bestAny) {
        state.nearestRentableStationId = null;
        el.textContent = t('mapGeoNearestNone', {
            name: stationDisplayName(bestAny.s),
            distance: formatDistanceForBanner(bestAny.d),
        });
        el.classList.add('is-muted');
    } else {
        state.nearestRentableStationId = null;
        el.textContent = '';
    }
}

function startMapGeolocationWatch() {
    if (!navigator.geolocation || geoWatchId !== null) return;
    const statusEl = document.getElementById('mapGeoStatus');
    if (statusEl) statusEl.textContent = t('mapGeoPending');
    geoWatchId = navigator.geolocation.watchPosition(
        (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            const acc = pos.coords.accuracy;
            state.userGeoLatRaw = lat;
            state.userGeoLngRaw = lng;
            state.userGeoAccRaw = acc;
            if (!umLatLngOk(lat, lng)) {
                if (statusEl) statusEl.textContent = t('mapGeoOutsideCampus');
                return;
            }
            if (statusEl) statusEl.textContent = '';
            if (!state.userGeoConfirmed) {
                showUserLocationConfirmBar(lat, lng, acc);
                return;
            }
            if (state.userCalibratedLat != null && state.userCalibratedLng != null) {
                applyUserDisplayedLocation();
                return;
            }
            state.userGeoLat = lat;
            state.userGeoLng = lng;
            drawUserLocation(lat, lng, acc);
            const prevNearest = state.nearestRentableStationId;
            updateNearestRentableBanner();
            if (String(prevNearest) !== String(state.nearestRentableStationId) && state.mapStations.length) {
                renderStationMap(state.mapStations);
            }
        },
        () => {
            if (statusEl) statusEl.textContent = t('mapGeoDenied');
        },
        { enableHighAccuracy: true, maximumAge: 4000, timeout: 20000 },
    );
}

function attachStationMapRelocateClick() {
    if (!stationMap || stationMap.__relocateAttached) return;
    stationMap.__relocateAttached = true;
    stationMap.on('click', (e) => {
        if (state.userGeoAdjustMode) {
            handleUserPositionMapPick(e.latlng.lat, e.latlng.lng);
            return;
        }
        if (!state.canEditStationCoordinates) return;
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        if (!umLatLngOk(lat, lng)) {
            showMiniPopup(t('mapRelocateOutOfBounds'), true);
            return;
        }
        state.pendingRelocate = { lat, lng };
        if (relocatePreviewLayer) {
            relocatePreviewLayer.clearLayers();
            L.marker([lat, lng], {
                icon: L.divIcon({
                    className: 'station-relocate-preview',
                    html: '<div class="station-relocate-preview-dot"></div>',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10],
                }),
            }).addTo(relocatePreviewLayer);
        }
        openStationRelocateModal(lat, lng);
    });
}

function nearestStationIdToPoint(lat, lng) {
    let bestId = null;
    let bestD = Infinity;
    (state.mapStations || []).forEach((s) => {
        const pos = normalizeStationLatLng(s);
        if (!pos) return;
        const d = calcDistanceKm(lat, lng, pos.lat, pos.lng);
        if (d < bestD) {
            bestD = d;
            bestId = s.id;
        }
    });
    return bestId;
}

function openStationRelocateModal(lat, lng) {
    const modal = document.getElementById('stationRelocateModal');
    const sel = document.getElementById('stationRelocateSelect');
    const coordsEl = document.getElementById('stationRelocateCoordsText');
    const bodyEl = document.getElementById('stationRelocateModalBody');
    if (!modal || !sel || !coordsEl) return;
    const latTxt = lat.toFixed(7);
    const lngTxt = lng.toFixed(7);
    coordsEl.textContent = `${latTxt}, ${lngTxt}`;
    sel.innerHTML = '';
    (state.mapStations || [])
        .slice()
        .sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')))
        .forEach((s) => {
            const opt = document.createElement('option');
            opt.value = String(s.id);
            opt.textContent = stationDisplayName(s);
            sel.appendChild(opt);
        });
    const guess = nearestStationIdToPoint(lat, lng);
    if (guess != null) sel.value = String(guess);
    const picked = state.mapStations.find((x) => String(x.id) === String(sel.value));
    if (bodyEl && picked) {
        bodyEl.textContent = t('mapRelocateBody', {
            name: stationDisplayName(picked),
            lat: latTxt,
            lng: lngTxt,
        });
    }
    sel.onchange = () => {
        const st = state.mapStations.find((x) => String(x.id) === String(sel.value));
        if (bodyEl && st && state.pendingRelocate) {
            bodyEl.textContent = t('mapRelocateBody', {
                name: stationDisplayName(st),
                lat: latTxt,
                lng: lngTxt,
            });
        }
    };
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
}

function closeStationRelocateModal() {
    const modal = document.getElementById('stationRelocateModal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
    state.pendingRelocate = null;
    if (relocatePreviewLayer) relocatePreviewLayer.clearLayers();
}

function renderStationMap(stations) {
    if (!window.L) return;
    if (!stationMap) {
        const southWest = L.latLng(UM_BOUNDS.south, UM_BOUNDS.west);
        const northEast = L.latLng(UM_BOUNDS.north, UM_BOUNDS.east);
        const mapBounds = L.latLngBounds(southWest, northEast);
        stationMap = L.map('stationMap', {
            minZoom: 14,
            // OSM tiles are native to z19; higher zooms overscale last tiles (avoids grey canvas).
            maxZoom: 22,
            maxBounds: mapBounds,
            maxBoundsViscosity: 1.0,
        }).fitBounds(mapBounds);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 22,
            maxNativeZoom: 19,
        }).addTo(stationMap);
        stationLayer = L.featureGroup().addTo(stationMap);
        userLocationLayer = L.layerGroup().addTo(stationMap);
        userPickPreviewLayer = L.layerGroup().addTo(stationMap);
        relocatePreviewLayer = L.layerGroup().addTo(stationMap);
        startMapGeolocationWatch();
        attachStationMapRelocateClick();
    }
    stationLayer.clearLayers();
    const list = Array.isArray(stations) ? stations : [];
    const nearestId = state.nearestRentableStationId != null ? String(state.nearestRentableStationId) : null;
    list.forEach((s) => {
        const pos = normalizeStationLatLng(s);
        if (!pos) return;
        const { lat, lng } = pos;
        const code = stationBuildingCode(s.name) || String(s.id);
        const isLong = code.length > 3;
        const isNearest = nearestId !== null && String(s.id) === nearestId;
        const label = escapeHtml(code);
        const icon = L.divIcon({
            className: 'station-map-pin-wrap',
            html: `<div class="station-map-pin${isLong ? ' station-map-pin--long' : ''}${isNearest ? ' station-map-pin--nearest' : ''}" role="img" aria-label="${escapeHtml(stationDisplayName(s))}"><span class="station-map-pin__code">${label}</span></div>`,
            iconSize: isLong ? [40, 36] : [36, 36],
            iconAnchor: isLong ? [20, 36] : [18, 36],
            popupAnchor: [0, -32],
        });
        const fullName = stationDisplayName(s);
        const marker = L.marker([lat, lng], { icon, title: fullName });
        marker.bindPopup(
            `<strong>${stationDisplayName(s)}</strong><br>${t('stationCapacity')}: ${s.capacity}<br>${t('stationBikes')}: ${s.available_bicycles}<br>${t('stationScooters')}: ${s.available_scooters}`
        );
        marker.addTo(stationLayer);
    });

    if (!mapDidAutoFitStations && stationLayer.getLayers().length > 0) {
        mapDidAutoFitStations = true;
        const b = stationLayer.getBounds();
        if (b.isValid()) {
            stationMap.fitBounds(b.pad(0.14));
        }
    }

    requestAnimationFrame(() => {
        if (stationMap) stationMap.invalidateSize();
    });
}

function renderOrders(orders) {
    const body = document.getElementById('orderBody');
    body.innerHTML = '';
    if (!orders || orders.length === 0) {
        body.innerHTML = `<tr><td colspan="8">${t('noOrdersPage')}</td></tr>`;
        return;
    }

    orders.forEach((o) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${o.order_no}</td>
          <td>${o.vehicle_type}</td>
          <td>${o.brand}</td>
          <td>${o.start_time}</td>
          <td>${o.end_time ?? '-'}</td>
          <td>${o.duration_minutes ?? '-'}</td>
          <td>${o.fee}</td>
          <td><span class="badge ${o.status}">${o.status}</span></td>
        `;
        body.appendChild(tr);
    });
}

function calcDistanceKm(lat1, lng1, lat2, lng2) {
    const toRad = (v) => v * Math.PI / 180;
    const R = 6371;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
}

function vehicleTypeLabel(vt) {
    const n = String(vt || '').toLowerCase();
    if (n === 'scooter') return t('typeScooter');
    if (n === 'bicycle') return t('typeBicycle');
    return vt ? String(vt) : '-';
}

function vehicleTypeClass(vt) {
    const n = String(vt || '').toLowerCase();
    if (n === 'scooter') return 'is-scooter';
    if (n === 'bicycle') return 'is-bicycle';
    return 'is-other';
}

function stationImageUrl(station) {
    const baseName = String(station?.name || '').trim();
    if (!baseName) return '';
    return `./assets/images/building/${encodeURIComponent(baseName)}.png`;
}

function renderStationPickCards(stations) {
    const wrap = document.getElementById('stationPickCards');
    const countEl = document.getElementById('resultsCountText');
    if (!wrap) return;
    const list = [...(stations || [])].sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));
    if (countEl) countEl.textContent = t('stationResultsCount', { count: list.length });
    wrap.innerHTML = '';
    if (list.length === 0) {
        wrap.innerHTML = `<div class="message">${t('noStationsAvailable')}</div>`;
        return;
    }
    list.forEach((s) => {
        const art = document.createElement('article');
        const sid = Number(s.id);
        const isSel = state.focusStationId !== null && Number(state.focusStationId) === sid;
        art.className = `station-pick-card${isSel ? ' is-selected' : ''}`;
        art.dataset.stationId = String(s.id);
        if (isSel) art.setAttribute('aria-current', 'true');
        else art.removeAttribute('aria-current');
        const stOk = String(s.station_status || 'active').toLowerCase() === 'active';
        const statusLabel = stOk ? t('stationStatusOpen') : t('stationStatusOther');
        const imgSrc = stationImageUrl(s);
        art.innerHTML = `
          <div class="station-pick-head">
            <strong>${stationDisplayName(s)}</strong>
            <span class="badge active">${statusLabel}</span>
          </div>
          <div class="station-pick-image-wrap">
            <img class="station-pick-image" src="${imgSrc}" alt="${stationDisplayName(s)}" loading="lazy" decoding="async">
          </div>
          <div class="station-pick-foot">
            <button type="button" class="pick-station-btn">${t('pickStationBtn')}</button>
          </div>`;
        const img = art.querySelector('.station-pick-image');
        if (img) {
            img.addEventListener('error', () => {
                const wrapEl = img.closest('.station-pick-image-wrap');
                if (wrapEl) wrapEl.classList.add('is-hidden');
            }, { once: true });
        }
        const openStation = () => { void focusStation(sid); };
        art.addEventListener('click', openStation);
        wrap.appendChild(art);
    });
}

function scrollVehiclePanelIntoView() {
    const panel = document.getElementById('vehicleListPanel');
    if (!panel || panel.classList.contains('is-hidden')) return;
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function focusStation(stationId) {
    const sid = Number(stationId);
    if (!Number.isFinite(sid) || sid <= 0) return;

    state.focusStationId = sid;
    const fs = document.getElementById('filterStation');
    if (fs) fs.value = String(sid);

    const st = state.stationsById[String(sid)];
    const heading = document.getElementById('vehicleListHeading');
    if (heading) {
        heading.textContent = st
            ? t('vehiclesAtStationTitle', { name: stationDisplayName(st) })
            : t('vehiclesAtStationTitle', { name: '…' });
    }
    const panel = document.getElementById('vehicleListPanel');
    if (panel) panel.classList.remove('is-hidden');
    state.bikePage = 1;
    await refreshDashboard();
    scrollVehiclePanelIntoView();
}

async function clearStationFocus() {
    state.focusStationId = null;
    const fs = document.getElementById('filterStation');
    if (fs) fs.value = '';
    const panel = document.getElementById('vehicleListPanel');
    if (panel) panel.classList.add('is-hidden');
    const cards = document.getElementById('bicycleCards');
    if (cards) cards.innerHTML = '';
    const bl = document.getElementById('bikeLoading');
    if (bl) bl.textContent = '';
    await refreshDashboard();
}

function renderAvailableBicycles(rows) {
    const body = document.getElementById('bicycleCards');
    body.innerHTML = '';
    const sortByEl = document.getElementById('sortBy');
    const sortBy = sortByEl ? sortByEl.value : 'price_asc';
    const sortedRows = [...(rows || [])].sort((a, b) => {
        const pa = Number(a.price_per_30_min || 0);
        const pb = Number(b.price_per_30_min || 0);
        return sortBy === 'price_desc' ? (pb - pa) : (pa - pb);
    });
    if (!sortedRows || sortedRows.length === 0) {
        body.innerHTML = `<div class="message">${t('noBikesFilter')}</div>`;
        return;
    }

    sortedRows.forEach((b) => {
        const typeTag = vehicleTypeLabel(b.vehicle_type);
        const typeClass = vehicleTypeClass(b.vehicle_type);
        const card = document.createElement('article');
        card.className = 'bike-card';
        card.dataset.bikeId = String(b.id);
        card.innerHTML = `
          <div class="bike-card-head">
            <strong>
              <span class="vehicle-type-tag ${typeClass}">${typeTag}</span>
              <span class="vehicle-inline-info"> · ${b.brand} · ${b.serial_no}</span>
            </strong>
            <button type="button" onclick="fillRentForm(${b.id})">${t('rentBtn')}</button>
          </div>
        `;
        body.appendChild(card);
    });
    window.__lastBikeRows = sortedRows;
}

function openRentModal() {
    const modal = document.getElementById('rentModal');
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
}

function openInsufficientBalanceModal() {
    const modal = document.getElementById('walletModal');
    if (!modal) return;
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
}

function closeInsufficientBalanceModal() {
    const modal = document.getElementById('walletModal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
}

function showInsufficientBalanceModal() {
    const body = document.getElementById('walletModalBody');
    if (body) body.textContent = t('walletModalBody');
    openInsufficientBalanceModal();
}

function openActiveOrderBlockedModal() {
    const modal = document.getElementById('activeOrderModal');
    if (!modal) return;
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
}

function closeActiveOrderBlockedModal() {
    const modal = document.getElementById('activeOrderModal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
}

function showActiveOrderBlockedModal() {
    const body = document.getElementById('activeOrderModalBody');
    if (body) body.textContent = t('activeOrderModalBody');
    closeRentModal();
    openActiveOrderBlockedModal();
}

function closeRentModal() {
    const modal = document.getElementById('rentModal');
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
}

function openRentSuccessModal() {
    const modal = document.getElementById('rentSuccessModal');
    if (!modal) return;
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
}

function closeRentSuccessModal() {
    const modal = document.getElementById('rentSuccessModal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
}

function renderRentSuccessModal(info = {}, bike = null) {
    const chosenBike = bike || state.selectedBike;
    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value ?? '-';
    };
    setText('successOrderIdValue', info.order_id != null ? String(info.order_id) : '-');
    setText('successStartTimeValue', info.start_time ? String(info.start_time) : '-');
    setText('successVehicleTypeValue', chosenBike ? vehicleTypeLabel(chosenBike.vehicle_type) : '-');
    setText('successVehicleIdValue', chosenBike ? String(chosenBike.id ?? '-') : '-');
    setText('successSerialValue', chosenBike ? String(chosenBike.serial_no ?? '-') : '-');
    setText('successBrandValue', chosenBike ? String(chosenBike.brand ?? '-') : '-');
    setText('successStationValue', chosenBike ? stationDisplayName(chosenBike) : '-');
    const price = chosenBike ? `MOP ${Number(chosenBike.price_per_30_min || 0).toFixed(2)} / 30 min` : '-';
    setText('successPriceValue', price);
    const summaryEl = document.getElementById('rentSuccessModalSummary');
    if (summaryEl) summaryEl.textContent = t('rentSuccessModalHint');
}

function openReturnConfirmModal() {
    const modal = document.getElementById('returnConfirmModal');
    if (!modal) return;
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
}

function closeReturnConfirmModal() {
    const modal = document.getElementById('returnConfirmModal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
}

function renderReturnConfirmModal(activeOrder, stationName) {
    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value ?? '-';
    };
    if (!activeOrder) return;
    const metrics = calcRentalMetrics(activeOrder.start_time, Number(activeOrder.price_per_30_min || 0), state.rentalPolicy);
    setText('returnModalOrderIdValue', String(activeOrder.order_no || '-'));
    setText('returnModalStartTimeValue', String(activeOrder.start_time || '-'));
    setText('returnModalVehicleTypeValue', vehicleTypeLabel(activeOrder.vehicle_type || '-'));
    setText('returnModalVehicleIdValue', String(activeOrder.vehicle_id ?? '-'));
    setText('returnModalSerialValue', String(activeOrder.serial_no || '-'));
    setText('returnModalBrandValue', String(activeOrder.brand || '-'));
    setText('returnModalStationValue', String(stationName || '-'));
    setText('returnModalPriceValue', `MOP ${Number(activeOrder.price_per_30_min || 0).toFixed(2)} / 30 min`);
    setText('returnModalTimeValue', metrics.durationText);
    setText('returnModalFeeValue', `MOP ${metrics.fare}`);
    const summaryEl = document.getElementById('returnConfirmModalSummary');
    if (summaryEl) summaryEl.textContent = t('returnConfirmModalHint');
}

function renderRentModal() {
    const bike = state.selectedBike;
    const mtv = document.getElementById('modalVehicleTypeValue');
    if (mtv) mtv.textContent = bike ? vehicleTypeLabel(bike.vehicle_type) : '-';
    document.getElementById('modalBikeIdValue').textContent = bike ? String(bike.id) : '-';
    document.getElementById('modalSerialValue').textContent = bike ? String(bike.serial_no) : '-';
    document.getElementById('modalBrandValue').textContent = bike ? String(bike.brand) : '-';
    document.getElementById('modalStationValue').textContent = bike ? stationDisplayName(bike) : '-';
    document.getElementById('modalBatteryValue').textContent = bike ? `${bike.battery_level ?? '-'}%` : '-';
    document.getElementById('modalPriceValue').textContent = bike ? `MOP ${Number(bike.price_per_30_min).toFixed(2)} / 30 min` : '-';
    document.getElementById('rentModalSummary').textContent = bike
        ? t('vehicleReady', { id: bike.id })
        : t('modalNoBike');
}

function renderSearchSummary() {
    const pickup = document.getElementById('pickupTime').value || '-';
    const dropoff = document.getElementById('dropoffTime').value || '-';
    const pickupSel = document.getElementById('pickupLocation');
    const station = pickupSel.options[pickupSel.selectedIndex]?.textContent || t('allStations');
    document.getElementById('searchSummary').textContent = t('searchSummary', { station, pickup, dropoff });
}

function renderBrands(brands) {
    const select = document.getElementById('filterBrand');
    select.innerHTML = `<option value="">${t('allBrands')}</option>` + brands.map((b) => `<option value="${b.id}">${b.name}</option>`).join('');
}

function parseRentalStartTime(s) {
    const raw = String(s ?? '').trim();
    if (!raw) return new Date(NaN);
    const d = new Date(raw);
    if (!Number.isNaN(d.getTime())) return d;
    return new Date(raw.replace(' ', 'T'));
}

function calcRentalMetrics(startTime, pricePer30, policy) {
    const p = policy && typeof policy.maxRentalMinutes === 'number' ? policy : state.rentalPolicy;
    const maxMin = Number(p.maxRentalMinutes) || 120;
    const penaltyMop = Number(p.overtimePenaltyMop) || 500;
    const start = parseRentalStartTime(startTime);
    const diff = Math.max(0, Date.now() - start.getTime());
    const totalSec = Math.floor(diff / 1000);
    const min = Math.floor(totalSec / 60);
    const sec = totalSec % 60;
    const billedMinutes = Math.max(1, Math.ceil(totalSec / 60));
    let fare = Number(pricePer30) > 0 ? (Math.ceil(billedMinutes / 30) * Number(pricePer30)) : 0;
    const isOvertime = billedMinutes > maxMin;
    if (isOvertime) {
        fare += penaltyMop;
    }
    const hoursLimit = (maxMin / 60).toString();
    const durationText = lang === 'en' ? `${min}m ${sec}s` : `${min}分 ${sec}秒`;
    return { durationText, fare: fare.toFixed(2), totalSec, isOvertime, hoursLimit, penaltyMop };
}

function bindTimer(startTime, activeOrder = null) {
    activeStartTime = parseRentalStartTime(startTime);
    if (timer) clearInterval(timer);
    const pricePer30 = Number(activeOrder && activeOrder.price_per_30_min != null ? activeOrder.price_per_30_min : 0);
    const vehicleLineEl = document.getElementById('rentalActiveVehicleText');
    if (vehicleLineEl && activeOrder) {
        vehicleLineEl.textContent = t('activeRentalVehicleLine', {
            type: vehicleTypeLabel(activeOrder.vehicle_type || '-'),
            brand: String(activeOrder.brand || '-'),
            serial: String(activeOrder.serial_no || '-'),
        });
    }
    const pol = state.rentalPolicy;
    const hintEl = document.getElementById('rentalPolicyHint');
    if (hintEl) {
        const h = (Number(pol.maxRentalMinutes) / 60).toString();
        hintEl.textContent = t('rentalPolicyShort', { hours: h, penalty: String(pol.overtimePenaltyMop) });
        hintEl.hidden = false;
    }
    const tick = () => {
        const metrics = calcRentalMetrics(startTime, pricePer30, pol);
        const timerEl = document.getElementById('timerText');
        const feeEl = document.getElementById('rentalFeeText');
        const overdueEl = document.getElementById('rentalOvertimeWarn');
        if (timerEl) {
            timerEl.innerHTML = `<span class="metric-label time">${t('activeRentalTimeLabel')}</span><span class="metric-value">${metrics.durationText}</span>`;
            timerEl.classList.add('success-metric-line');
            timerEl.classList.toggle('is-overtime', metrics.isOvertime);
        }
        if (feeEl) {
            feeEl.innerHTML = `<span class="metric-label fee">${t('activeRentalFeeLabel')}</span><span class="metric-value">MOP ${metrics.fare}</span>`;
            feeEl.classList.add('success-metric-line');
        }
        if (overdueEl) {
            if (metrics.isOvertime) {
                overdueEl.textContent = t('rentalOvertimeWarning', {
                    hours: metrics.hoursLimit,
                    penalty: String(metrics.penaltyMop),
                });
                overdueEl.classList.remove('is-hidden');
            } else {
                overdueEl.textContent = '';
                overdueEl.classList.add('is-hidden');
            }
        }
    };
    tick();
    timer = setInterval(tick, 1000);
}

function clearTimer() {
    if (timer) clearInterval(timer);
    timer = null;
    const timerEl = document.getElementById('timerText');
    if (timerEl) timerEl.textContent = '';
    const feeEl = document.getElementById('rentalFeeText');
    if (feeEl) feeEl.textContent = '';
    const vehicleLineEl = document.getElementById('rentalActiveVehicleText');
    if (vehicleLineEl) vehicleLineEl.textContent = '';
    const hintEl = document.getElementById('rentalPolicyHint');
    if (hintEl) {
        hintEl.textContent = '';
        hintEl.hidden = true;
    }
    const overdueEl = document.getElementById('rentalOvertimeWarn');
    if (overdueEl) {
        overdueEl.textContent = '';
        overdueEl.classList.add('is-hidden');
    }
}

function showRentalSuccessCard(text) {
    const card = document.getElementById('rentalSuccessCard');
    const textEl = document.getElementById('rentalSuccessText');
    if (!card || !textEl) return;
    textEl.textContent = text;
    card.classList.remove('is-hidden');
}

function hideRentalSuccessCard() {
    const card = document.getElementById('rentalSuccessCard');
    if (card) card.classList.add('is-hidden');
}

function updateReturnUi(activeOrder) {
    const noRide = document.getElementById('returnNoRideMsg');
    const form = document.getElementById('returnRideForm');
    const meta = document.getElementById('returnActiveTripMeta');
    if (!noRide || !form) return;
    if (activeOrder) {
        noRide.classList.add('is-hidden');
        form.classList.remove('is-hidden');
        if (meta) {
            meta.textContent = t('returnActiveTripLine', {
                order: activeOrder.order_no || '',
                started: activeOrder.start_time || '',
            });
        }
    } else {
        noRide.classList.remove('is-hidden');
        form.classList.add('is-hidden');
        if (meta) meta.textContent = '';
        hideReturnAlternatives();
    }
}

async function refreshDashboard() {
    const bl = document.getElementById('bikeLoading');
    if (bl) bl.textContent = t('loadingBikes');
    document.getElementById('orderLoading').textContent = t('loadingOrders');
    if (state.focusStationId) {
        const fs = document.getElementById('filterStation');
        if (fs) fs.value = String(state.focusStationId);
    }
    const stationVal = state.focusStationId
        ? String(state.focusStationId)
        : (document.getElementById('filterStation')?.value || '');
    const brandVal = document.getElementById('filterBrand').value;
    const batteryVal = document.getElementById('filterBattery').value;
    const data = await api('get_dashboard', 'GET', null, {
        bikePage: state.bikePage,
        orderPage: state.orderPage,
        stationID: stationVal || null,
        brandID: brandVal || null,
        minBattery: batteryVal || null,
    });
    renderStations(data.data.stations);
    renderBrands(data.data.brands);
    renderOrders(data.data.orders);
    renderStationPickCards(data.data.stations);
    if (state.focusStationId) {
        const st = state.stationsById[String(state.focusStationId)];
        const heading = document.getElementById('vehicleListHeading');
        if (heading && st) heading.textContent = t('vehiclesAtStationTitle', { name: stationDisplayName(st) });
        renderAvailableBicycles(data.data.availableBicycles);
    } else {
        const cards = document.getElementById('bicycleCards');
        if (cards) cards.innerHTML = '';
        if (bl) bl.textContent = '';
    }
    state.bikesTotalPages = Number(data.data.pagination?.bikes?.totalPages || 1);
    state.ordersTotalPages = Number(data.data.pagination?.orders?.totalPages || 1);
    document.getElementById('bikePageText').textContent = t('bikePage', { page: state.bikePage, total: state.bikesTotalPages });
    document.getElementById('orderPageText').textContent = t('orderPage', { page: state.orderPage, total: state.ordersTotalPages });
    if (state.focusStationId) {
        if (bl) bl.textContent = (data.data.availableBicycles && data.data.availableBicycles.length) ? '' : t('noBikesFilter');
    }
    document.getElementById('orderLoading').textContent = data.data.orders.length ? '' : t('noOrdersPage');
    state.activeOrder = data.data.activeOrder || null;
    if (data.data.rentalPolicy && typeof data.data.rentalPolicy.maxRentalMinutes === 'number') {
        state.rentalPolicy = {
            maxRentalMinutes: Number(data.data.rentalPolicy.maxRentalMinutes),
            overtimePenaltyMop: Number(data.data.rentalPolicy.overtimePenaltyMop) || 500,
        };
    }
    if (data.data.mapUi) {
        state.canEditStationCoordinates = Boolean(data.data.mapUi.canEditStationCoordinates);
        const rh = document.getElementById('mapRelocateStaffHint');
        if (rh) {
            rh.classList.toggle('is-hidden', !state.canEditStationCoordinates);
            if (state.canEditStationCoordinates) rh.textContent = t('mapRelocateHintStaff');
        }
    }
    if (data.data.activeOrder) {
        showRentalSuccessCard(t('rentalSuccessHint'));
        bindTimer(data.data.activeOrder.start_time, data.data.activeOrder);
    } else {
        hideRentalSuccessCard();
        clearTimer();
        closeReturnConfirmModal();
    }
    updateReturnUi(data.data.activeOrder);
}

function fillRentForm(vehicleId) {
    document.querySelectorAll('.bike-card').forEach((c) => c.classList.remove('selected'));
    const selectedCard = document.querySelector(`.bike-card[data-bike-id="${vehicleId}"]`);
    if (selectedCard) selectedCard.classList.add('selected');
    const selectedRow = (window.__lastBikeRows || []).find((r) => Number(r.id) === Number(vehicleId)) || null;
    state.selectedBike = selectedRow;
    renderRentModal();
    openRentModal();
    showMiniPopup(t('vehicleReadyToast', { id: vehicleId }));
}

const openRentModalBtn = document.getElementById('openRentModalBtn');
if (openRentModalBtn) {
    openRentModalBtn.addEventListener('click', () => {
        if (!state.selectedBike) {
            showMiniPopup(t('modalNoBike'), true);
            return;
        }
        renderRentModal();
        openRentModal();
    });
}
const closeRentModalBtn = document.getElementById('closeRentModalBtn');
if (closeRentModalBtn) closeRentModalBtn.addEventListener('click', closeRentModal);
const rentModalEl = document.getElementById('rentModal');
if (rentModalEl) {
    rentModalEl.addEventListener('click', (e) => {
        if (e.target.id === 'rentModal') closeRentModal();
    });
}
document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    const userPosModalOpen = document.getElementById('userPositionPickModal')?.classList.contains('show');
    if (userPosModalOpen || state.userGeoAdjustMode) {
        cancelUserPositionPickFlow();
        return;
    }
    closeRentModal();
    closeRentSuccessModal();
    closeReturnConfirmModal();
    closeStationRelocateModal();
    closeInsufficientBalanceModal();
    closeActiveOrderBlockedModal();
});

const mapLocateMeBtn = document.getElementById('mapLocateMeBtn');
if (mapLocateMeBtn) {
    mapLocateMeBtn.addEventListener('click', () => {
        if (!state.userGeoConfirmed) {
            showMiniPopup(t('userLocWaitConfirm'), true);
            document.getElementById('userLocationConfirmBar')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return;
        }
        if (state.userGeoLat != null && state.userGeoLng != null && stationMap) {
            stationMap.setView([state.userGeoLat, state.userGeoLng], 17);
        } else {
            showMiniPopup(t('mapGeoPending'), false);
        }
    });
}
const mapRefreshGpsBtn = document.getElementById('mapRefreshGpsBtn');
if (mapRefreshGpsBtn) {
    mapRefreshGpsBtn.addEventListener('click', () => refreshUserGpsOnce());
}
const userLocationAdjustMapBtn = document.getElementById('userLocationAdjustMapBtn');
if (userLocationAdjustMapBtn) {
    userLocationAdjustMapBtn.addEventListener('click', () => {
        hideUserLocationConfirmBar();
        enterUserGeoPickMode();
    });
}
const userLocationConfirmUseBtn = document.getElementById('userLocationConfirmUseBtn');
if (userLocationConfirmUseBtn) {
    userLocationConfirmUseBtn.addEventListener('click', () => {
        state.userGeoConfirmed = true;
        state.userCalibratedLat = null;
        state.userCalibratedLng = null;
        hideUserLocationConfirmBar();
        exitUserGeoPickMode();
        applyUserDisplayedLocation();
    });
}
const userLocationPickMapBtn = document.getElementById('userLocationPickMapBtn');
if (userLocationPickMapBtn) {
    userLocationPickMapBtn.addEventListener('click', () => {
        hideUserLocationConfirmBar();
        enterUserGeoPickMode();
    });
}
const userLocationResetGpsBtn = document.getElementById('userLocationResetGpsBtn');
if (userLocationResetGpsBtn) {
    userLocationResetGpsBtn.addEventListener('click', () => {
        state.userCalibratedLat = null;
        state.userCalibratedLng = null;
        applyUserDisplayedLocation();
        showMiniPopup(t('mapLocateMe'), false);
    });
}
const confirmUserPositionPickBtn = document.getElementById('confirmUserPositionPickBtn');
if (confirmUserPositionPickBtn) {
    confirmUserPositionPickBtn.addEventListener('click', () => {
        const plat = state.pendingUserPickLat;
        const plng = state.pendingUserPickLng;
        if (plat == null || plng == null) return;
        state.userCalibratedLat = plat;
        state.userCalibratedLng = plng;
        state.userGeoConfirmed = true;
        exitUserGeoPickMode();
        closeUserPositionPickModal();
        hideUserLocationConfirmBar();
        applyUserDisplayedLocation();
        showMiniPopup(t('userPosSaved'));
    });
}
const cancelUserPositionPickBtn = document.getElementById('cancelUserPositionPickBtn');
if (cancelUserPositionPickBtn) {
    cancelUserPositionPickBtn.addEventListener('click', cancelUserPositionPickFlow);
}
const closeUserPositionPickModalBtn = document.getElementById('closeUserPositionPickModalBtn');
if (closeUserPositionPickModalBtn) {
    closeUserPositionPickModalBtn.addEventListener('click', cancelUserPositionPickFlow);
}
const userPositionPickModalEl = document.getElementById('userPositionPickModal');
if (userPositionPickModalEl) {
    userPositionPickModalEl.addEventListener('click', (e) => {
        if (e.target.id === 'userPositionPickModal') cancelUserPositionPickFlow();
    });
}
const confirmStationRelocateBtn = document.getElementById('confirmStationRelocateBtn');
if (confirmStationRelocateBtn) {
    confirmStationRelocateBtn.addEventListener('click', async () => {
        const sel = document.getElementById('stationRelocateSelect');
        const pr = state.pendingRelocate;
        if (!pr || !sel) {
            closeStationRelocateModal();
            return;
        }
        const sid = Number(sel.value || 0);
        if (!sid) return;
        try {
            await api('update_station_location', 'POST', {
                station_id: sid,
                latitude: pr.lat,
                longitude: pr.lng,
            });
            closeStationRelocateModal();
            showMiniPopup(t('mapRelocateSuccess'));
            await refreshDashboard();
        } catch (e) {
            showMappedPopup(e.message, true, e.i18nKey || '');
            const errorKey = e.i18nKey || mapApiMessageToI18nKey(e.message);
            const errorCode = String(e.code || '').toUpperCase();
            if (errorKey === 'errInsufficientBalance' || errorCode === 'INSUFFICIENT_BALANCE') {
                showInsufficientBalanceModal();
            }
            if (errorKey === 'errUserHasActiveOrder') {
                showActiveOrderBlockedModal();
            }
        }
    });
}
const cancelStationRelocateBtn = document.getElementById('cancelStationRelocateBtn');
if (cancelStationRelocateBtn) cancelStationRelocateBtn.addEventListener('click', closeStationRelocateModal);
const closeStationRelocateModalBtn = document.getElementById('closeStationRelocateModalBtn');
if (closeStationRelocateModalBtn) closeStationRelocateModalBtn.addEventListener('click', closeStationRelocateModal);
const stationRelocateModalEl = document.getElementById('stationRelocateModal');
if (stationRelocateModalEl) {
    stationRelocateModalEl.addEventListener('click', (e) => {
        if (e.target.id === 'stationRelocateModal') closeStationRelocateModal();
    });
}
const closeWalletModalBtn = document.getElementById('closeWalletModalBtn');
if (closeWalletModalBtn) closeWalletModalBtn.addEventListener('click', closeInsufficientBalanceModal);
const walletModalDismissBtn = document.getElementById('walletModalDismissBtn');
if (walletModalDismissBtn) walletModalDismissBtn.addEventListener('click', closeInsufficientBalanceModal);
const walletModalTopupBtn = document.getElementById('walletModalTopupBtn');
if (walletModalTopupBtn) {
    walletModalTopupBtn.addEventListener('click', () => {
        window.location.href = './account.php';
    });
}
const walletModalEl = document.getElementById('walletModal');
if (walletModalEl) {
    walletModalEl.addEventListener('click', (e) => {
        if (e.target.id === 'walletModal') closeInsufficientBalanceModal();
    });
}
const closeActiveOrderModalBtn = document.getElementById('closeActiveOrderModalBtn');
if (closeActiveOrderModalBtn) closeActiveOrderModalBtn.addEventListener('click', closeActiveOrderBlockedModal);
const activeOrderModalDismissBtn = document.getElementById('activeOrderModalDismissBtn');
if (activeOrderModalDismissBtn) activeOrderModalDismissBtn.addEventListener('click', closeActiveOrderBlockedModal);
const activeOrderModalGoReturnBtn = document.getElementById('activeOrderModalGoReturnBtn');
if (activeOrderModalGoReturnBtn) {
    activeOrderModalGoReturnBtn.addEventListener('click', () => {
        closeActiveOrderBlockedModal();
        emphasizeReturnSection();
    });
}
const activeOrderModalEl = document.getElementById('activeOrderModal');
if (activeOrderModalEl) {
    activeOrderModalEl.addEventListener('click', (e) => {
        if (e.target.id === 'activeOrderModal') closeActiveOrderBlockedModal();
    });
}

const returnRideBtn = document.getElementById('returnRideBtn');
if (returnRideBtn) {
    returnRideBtn.addEventListener('click', () => {
        const sel = document.getElementById('returnStationSelect');
        const sid = Number(sel?.value || 0);
        if (!sid) {
            showMiniPopup(t('chooseReturnStation'), true);
            return;
        }
        if (!state.activeOrder) {
            showMiniPopup(t('errNoActiveOrder'), true);
            return;
        }
        const stationName = sel.options[sel.selectedIndex]?.textContent || '-';
        renderReturnConfirmModal(state.activeOrder, stationName);
        openReturnConfirmModal();
    });
}

const confirmReturnModalBtn = document.getElementById('confirmReturnModalBtn');
if (confirmReturnModalBtn) {
    confirmReturnModalBtn.addEventListener('click', async () => {
        const sel = document.getElementById('returnStationSelect');
        const sid = Number(sel?.value || 0);
        if (!sid) {
            showMiniPopup(t('chooseReturnStation'), true);
            return;
        }
        try {
            hideReturnAlternatives();
            await api('return', 'POST', { return_station_id: sid });
            closeReturnConfirmModal();
            showMiniPopup(t('returnCompleted'));
            await refreshDashboard();
            document.getElementById('returnRideSection')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } catch (e) {
            if (e.alternatives && e.alternatives.length) {
                showReturnAlternatives(e.alternatives);
            } else {
                hideReturnAlternatives();
            }
            showMappedPopup(e.message, true, e.i18nKey || '');
        }
    });
}

const confirmRentModalBtn = document.getElementById('confirmRentModalBtn');
if (confirmRentModalBtn) {
    confirmRentModalBtn.addEventListener('click', async () => {
        const vehicleId = Number(state.selectedBike?.id || 0);
        if (!vehicleId) {
            showMiniPopup(t('modalNoBike'), true);
            return;
        }
        try {
            const rentedBike = state.selectedBike ? { ...state.selectedBike } : null;
            const data = await api('rent', 'POST', { vehicle_id: vehicleId });
            const info = data.data || {};
            showRentalSuccessCard(
                `${t('rentalSuccessTitle')} | Order #${info.order_id ?? '-'} | Start: ${info.start_time ?? '-'} | ${info.campus_id ?? '-'} / ${info.full_name ?? '-'}`
            );
            renderRentSuccessModal(info, rentedBike);
            openRentSuccessModal();
            closeRentModal();
            await refreshDashboard();
            showMiniPopup(t('rentalStarted'));
        } catch (e) {
            showMappedPopup(e.message, true, e.i18nKey || '');
        }
    });
}

document.getElementById('applyFiltersBtn').addEventListener('click', async () => {
    state.bikePage = 1;
    await refreshDashboard();
});
document.getElementById('searchRideBtn').addEventListener('click', async () => {
    const stationId = document.getElementById('pickupLocation').value;
    const pickupStation = state.stationsById[String(stationId)] || null;
    state.selectedPickupLat = pickupStation ? Number(pickupStation.latitude) : null;
    state.selectedPickupLng = pickupStation ? Number(pickupStation.longitude) : null;
    renderSearchSummary();
    state.bikePage = 1;
    if (stationId) {
        await focusStation(Number(stationId));
    } else {
        await clearStationFocus();
    }
    document.getElementById('resultsCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
});
const sortByEl = document.getElementById('sortBy');
if (sortByEl) {
    sortByEl.addEventListener('change', async () => {
        state.bikePage = 1;
        await refreshDashboard();
    });
}
document.getElementById('bikePrevBtn').addEventListener('click', async () => {
    if (!state.focusStationId) return;
    if (state.bikePage <= 1) return;
    state.bikePage -= 1;
    await refreshDashboard();
});
document.getElementById('bikeNextBtn').addEventListener('click', async () => {
    if (!state.focusStationId) return;
    if (state.bikePage >= state.bikesTotalPages) return;
    state.bikePage += 1;
    await refreshDashboard();
});
document.getElementById('orderPrevBtn').addEventListener('click', async () => {
    if (state.orderPage <= 1) return;
    state.orderPage -= 1;
    await refreshDashboard();
});
document.getElementById('orderNextBtn').addEventListener('click', async () => {
    if (state.orderPage >= state.ordersTotalPages) return;
    state.orderPage += 1;
    await refreshDashboard();
});

document.getElementById('filterStation').addEventListener('change', async () => {
    const v = document.getElementById('filterStation').value;
    if (!v) {
        await clearStationFocus();
        return;
    }
    await focusStation(Number(v));
});
const clearStationBtn = document.getElementById('clearStationFocusBtn');
if (clearStationBtn) clearStationBtn.addEventListener('click', () => { void clearStationFocus(); });
const closeRentSuccessModalBtn = document.getElementById('closeRentSuccessModalBtn');
if (closeRentSuccessModalBtn) closeRentSuccessModalBtn.addEventListener('click', closeRentSuccessModal);
const ackRentSuccessBtn = document.getElementById('ackRentSuccessBtn');
if (ackRentSuccessBtn) ackRentSuccessBtn.addEventListener('click', closeRentSuccessModal);
const rentSuccessModalEl = document.getElementById('rentSuccessModal');
if (rentSuccessModalEl) {
    rentSuccessModalEl.addEventListener('click', (e) => {
        if (e.target.id === 'rentSuccessModal') closeRentSuccessModal();
    });
}
const closeReturnConfirmModalBtn = document.getElementById('closeReturnConfirmModalBtn');
if (closeReturnConfirmModalBtn) closeReturnConfirmModalBtn.addEventListener('click', closeReturnConfirmModal);
const returnConfirmModalEl = document.getElementById('returnConfirmModal');
if (returnConfirmModalEl) {
    returnConfirmModalEl.addEventListener('click', (e) => {
        if (e.target.id === 'returnConfirmModal') closeReturnConfirmModal();
    });
}

refreshDashboard().catch((err) => {
    showMappedPopup(err.message, true, err.i18nKey || '');
});
const langSelect = document.getElementById('languageSelect');
if (langSelect) {
    langSelect.value = lang;
    langSelect.addEventListener('change', async () => {
        lang = langSelect.value;
        localStorage.setItem('lang', lang);
        applyLanguage();
        await refreshDashboard();
    });
}
applyLanguage();
function toLocalDateTimeValue(date) {
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}
const now = new Date();
const plusThreeDays = new Date(now.getTime() + (3 * 24 * 60 * 60 * 1000));
document.getElementById('pickupTime').value = toLocalDateTimeValue(now);
document.getElementById('dropoffTime').value = toLocalDateTimeValue(plusThreeDays);
renderSearchSummary();
</script>
</body>
</html>
