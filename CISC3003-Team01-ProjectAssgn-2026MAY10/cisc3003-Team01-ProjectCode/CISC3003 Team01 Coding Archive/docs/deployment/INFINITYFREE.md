# InfinityFree 免費 PHP + MySQL 上架指南

適用本專案（PHP + PDO MySQL）。官方政策與介面會變，以下為通用流程；請同時以 [InfinityFree 控制台](https://dash.infinityfree.com/) 為準。

---

## 1. 註冊與網站

1. 前往 **InfinityFree** 註冊帳號。
2. 建立網站（Website），選免費子網域（例如 `yoursite.epizy.com`，實際後綴依官方為準）或依指示綁定網域。
3. 記下 **FTP 主機、使用者名稱、密碼**（檔案管理或 FTP 區）。

---

## 2. 建立 MySQL 資料庫

1. 控制台進入 **MySQL Databases**（名稱可能略有不同）。
2. **建立資料庫與使用者**（共享主機通常會給你固定格式的資料庫名、使用者名，例如 `epiz_xxxxxx_dbname`）。
3. 請抄下以下四項（部署必填）：
   - **MySQL Hostname**（常為 `sqlXXX.infinityfree.com` 類似格式，**請勿臆測**，以控制台為準）
   - **Database name**
   - **MySQL username**
   - **Password**

埠號通常為 **3306**（與本機 XAMPP 若設 `3307` 不同，務必改成 `3306`）。

---

## 3. 上傳網站檔案

使用 **FTP**（FileZilla、WinSCP 等）連線到主機，將本專案**整包上傳**至網站根目錄（常為 `htdocs` 或 `public_html`，依控制台說明）。

需包含例如：`api/`、`assets/`、`config/`、`includes/`、`admin/`、`staff/`、根目錄各 `.php`、`.htaccess` 等。

**不要**依賴訪客連到你電腦：資料必須放在 InfinityFree 提供的 MySQL，見下一節。

---

## 4. 匯入資料表（重要）

共享主機通常**不允許**你在 SQL 裡自行 `CREATE DATABASE`，須使用控制台已建立的那個資料庫。

1. 控制台開啟 **phpMyAdmin**，進入你建立的那個資料庫。
2. 點 **匯入（Import）**，選擇本倉庫檔案：  
   **`database/schema_shared_hosting.sql`**  
   （已省略 `CREATE DATABASE` / `USE`，可直接匯入當前選定的庫。）
3. 若曾失敗過，可先刪除同名表再重新匯入，或改用「SQL」分頁分段執行。

若你改用完整版 `database/schema.sql`，請先手動刪除檔案開頭的 `CREATE DATABASE` 與 `USE ...` 兩段再匯入。

若有額外 migration（`database/migration_*.sql`），請依團隊需要在 phpMyAdmin 另行執行。

---

## 5. 設定資料庫連線（`config/db_connect.php`）

本機預設為 `127.0.0.1`、`3307`、`um_rental_system`。在 InfinityFree 必須改為**控制台給你的值**。

### 作法 A（建議）：`config/db_connect.local.php`（密碼不進 Git）

1. 在專案 `config/` 下複製 **`db_connect.local.example.php`** → **`db_connect.local.php`**。  
2. 填入控制台中的 **MySQL 主機名、埠 3306、完整資料庫名稱、使用者、密碼**，並設 **`DEBUG_MODE` => false**。  
3. `db_connect.local.php` 已列於 **`.gitignore`**，請勿改名後提交到公開倉庫。  
4. 連 FTP 上傳時一併上傳 **`db_connect.local.php`**（僅在伺服器存在即可）。

此檔由 **`config/db_env.php`** 統一讀取：**首頁 `home.php`（經 `includes/db.php`）與 `config.php` 的 API 都會使用**，單一設定即可，避免出現首頁 **HTTP 500** 但誤以為只改 `db_connect.php` 就夠的情況。

### 作法 B：直接修改伺服器上的 `db_connect.php`（不建議；易誤提交密碼）

上傳後編輯（或本機改好再上傳）對應變數邏輯：透過環境變數讀取時，在 InfinityFree 設定：

| 變數 | 範例含義 |
|------|-----------|
| `DB_HOST` | 控制台顯示的 MySQL Hostname |
| `DB_PORT` | `3306` |
| `DB_NAME` | 控制台給的資料庫名（完整字串） |
| `DB_USER` | 控制台給的 MySQL 使用者 |
| `DB_PASS` | 該使用者密碼 |

若主機**未**提供環境變數設定，請優先使用 **作法 A** 的 `db_connect.local.php`，勿把密碼寫進會提交的 `db_connect.php`。

### 作法 C：`.htaccess` 設定環境變數（若主機允許）

在網站根目錄 `.htaccess` 末尾（若支援 `mod_env`）可嘗試：

```apache
<IfModule mod_env.c>
    SetEnv DB_HOST sqlXXX.infinityfree.com
    SetEnv DB_PORT 3306
    SetEnv DB_NAME epiz_xxxxxxxx_yourdbname
    SetEnv DB_USER epiz_xxxxxxxx
    SetEnv DB_PASS your_mysql_password
</IfModule>
```

將上面替換成你控制台的真實值。部署後若 `getenv` 讀不到，改回作法 A。

### 正式環境請關閉除錯訊息

將 `config/db_connect.php` 內 **`$debugMode = false`**，避免對外顯示資料庫錯誤細節。

---

## 6. 首頁與網址

本專案根目錄 `.htaccess` 已設 **`DirectoryIndex home.php`**；部分免費主機仍只認 **`index.php`**，因此倉庫內含 **`index.php`**（內容為載入 `home.php`），可避免根網址出現 **403 Forbidden**。

請確認 **`index.php` 與 `home.php` 都在 `htdocs` 根目錄**（與 `.htaccess` 同層），不要只上傳到子資料夾。

若連線仍錯誤，請檢查：

- MySQL Hostname 是否與控制台**完全一致**
- 埠號是否為 **3306**
- 資料庫是否已匯入且名稱與 `DB_NAME` 一致

---

## 7. 已知限制（免費方案）

- 可能有 **CPU / 連線數 / 檔案數** 限制與休眠策略；長時間無流量可能被暫停（依官方說明）。
- **發信（mail）** 可能被限制；若功能依賴寄信需另外評估。
- Google OAuth 等需在 Google Cloud Console 將**正式網址**加入重新導向 URI。
- 政策與介面隨時更新，上架前請再看官方文件。

---

## 8. HTTP 500 排查順序

1. **`https://你的網域/health_ok.php`**  
   - 若顯示 **`ok`**：PHP 正常、`.htaccess` 大致可用。  
   - 若仍是 **500**：暫時把 **`htdocs/.htaccess`** 改名為 `.htaccess.off` 再試；若立刻恢復，代表規則與主機不相容（請用倉庫最新 `.htaccess`，已做 Apache 2.2/2.4 相容）。

2. **`https://你的網域/health_db.php`**  
   - 若顯示 **`db_ok`**：資料庫連線正常，首頁問題多半是缺檔或路徑。  
   - 若 **`db_fail: ...`**：依錯誤訊息檢查 **`config/db_connect.local.php`**（主機名、埠 `3306`、**完整 `DB_NAME`**、密碼），並確認已匯入 **`database/schema_shared_hosting.sql`**。

3. **檔案結構**：網站須在 **`htdocs` 根目錄**（與 `index.php`、`config/`、`includes/` 同層）。勿只把舊檔放在 `htdocs`、完整程式留在 `htdocs/project/` 而讓根目錄缺 **`config/db_env.php`**。

4. 診斷完成後請 **刪除或改名** `health_db.php`，避免對外暴露錯誤細節。

---

## 9. 檢查清單

- [ ] FTP 已上傳完整 PHP 專案  
- [ ] phpMyAdmin 已匯入 `schema_shared_hosting.sql`  
- [ ] `DB_HOST` / `DB_PORT` / `DB_NAME` / `DB_USER` / `DB_PASS` 已對應 InfinityFree  
- [ ] `$debugMode = false`  
- [ ] 瀏覽器開首頁、註冊/登入、儀表板有資料  

---

## 與本機開發並存

本機仍可用 `127.0.0.1`、`schema.sql` 建 `um_rental_system`；線上使用 InfinityFree 給的庫名與 `schema_shared_hosting.sql`，兩邊設定不要混在同一個 `db_connect.php` 提交進公開分支——建議線上改檔或用環境變數區分。
