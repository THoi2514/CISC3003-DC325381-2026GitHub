# A+B 模組啟動說明

## A - Flutter 前台 APP（掃碼 + 地圖找車）

目錄：`frontend_flutter`

1. 安裝依賴
   - `flutter pub get`
2. 啟動
   - `flutter run`
3. API Base URL
   - 檔案：`frontend_flutter/lib/services/api_service.dart`
   - Android 模擬器使用 `http://10.0.2.2/project/api`
   - iOS 模擬器可改 `http://localhost/project/api`
   - 實機請改成你電腦的區網 IP

### 掃碼格式（目前 MVP）
- `vehicleID:startStationID`
- 例：`12:3`

## B - React 後台管理儀表板

目錄：`admin_dashboard`

1. 安裝依賴
   - `npm install`
2. 啟動開發伺服器
   - `npm run dev`
3. 開啟
   - `http://localhost:5174`

## 後端需求

確保 XAMPP 的 Apache + MySQL 已啟動，並可透過：
- `http://localhost/project/api/stations.php`
- `http://localhost/project/api/start_rental.php`

同時需先登入建立 Session，`start_rental.php` 才會回應成功。
