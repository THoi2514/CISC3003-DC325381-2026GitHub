export default function OrderAndVehiclePanels() {
  return (
    <div className="two-cols">
      <section className="panel">
        <h2>異常訂單監控</h2>
        <p className="muted">待串接：超時未還、付款待處理、手動強制結單。</p>
        <ul>
          <li>欄位建議：OrderID / User / Vehicle / 開始時間 / 當前時長 / 狀態</li>
          <li>操作建議：Force End / Fee Adjust / Waive Fee（需審計日誌）</li>
        </ul>
      </section>

      <section className="panel">
        <h2>車輛狀態分佈</h2>
        <p className="muted">待串接：available / rented / maintenance / retired</p>
        <ul>
          <li>操作建議：維修標記、報廢標記、批次回站</li>
          <li>規則：禁止 retired 轉回 rented</li>
        </ul>
      </section>
    </div>
  );
}
