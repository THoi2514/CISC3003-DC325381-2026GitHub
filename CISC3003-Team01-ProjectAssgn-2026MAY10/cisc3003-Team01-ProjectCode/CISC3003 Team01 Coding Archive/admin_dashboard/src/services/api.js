const BASE = 'http://localhost/project/api';

export async function fetchStations() {
  const res = await fetch(`${BASE}/stations.php`);
  const data = await res.json();
  if (!res.ok || !data.success) throw new Error(data.message || '載入站點失敗');
  return data.data ?? [];
}

// 示範用：若無後端清單 API，先以站點資料組裝 dashboard
export async function fetchDashboardData() {
  const stations = await fetchStations();
  const totalStations = stations.length;
  const totalAvailable = stations.reduce((sum, s) => sum + Number(s.availableVehicles || 0), 0);
  const totalCapacity = stations.reduce((sum, s) => sum + Number(s.capacity || 0), 0);
  const usageRate = totalCapacity > 0 ? Math.round((totalAvailable / totalCapacity) * 100) : 0;

  return {
    kpi: {
      totalStations,
      totalAvailable,
      totalCapacity,
      usageRate,
    },
    stations,
    vehicles: [],
    orders: [],
  };
}
