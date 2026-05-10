import { useEffect, useState } from 'react';
import { fetchDashboardData } from './services/api';
import KpiCards from './components/KpiCards';
import StationTable from './components/StationTable';
import OrderAndVehiclePanels from './components/OrderAndVehiclePanels';

export default function App() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [data, setData] = useState({
    kpi: { totalStations: 0, totalAvailable: 0, totalCapacity: 0, usageRate: 0 },
    stations: [],
  });

  useEffect(() => {
    let active = true;
    async function run() {
      try {
        setLoading(true);
        const result = await fetchDashboardData();
        if (active) setData(result);
      } catch (e) {
        if (active) setError(String(e));
      } finally {
        if (active) setLoading(false);
      }
    }
    run();
    return () => {
      active = false;
    };
  }, []);

  return (
    <main className="page">
      <header className="header">
        <h1>UM 後台管理儀表板</h1>
        <p>站點容量、車輛狀態、訂單異常監控（MVP）</p>
      </header>

      {loading && <p>載入中...</p>}
      {error && <p className="error">錯誤：{error}</p>}

      {!loading && !error && (
        <>
          <KpiCards kpi={data.kpi} />
          <OrderAndVehiclePanels />
          <StationTable stations={data.stations} />
        </>
      )}
    </main>
  );
}
