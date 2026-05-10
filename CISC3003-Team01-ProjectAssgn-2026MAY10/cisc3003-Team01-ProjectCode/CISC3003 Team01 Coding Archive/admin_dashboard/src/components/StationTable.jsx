export default function StationTable({ stations }) {
  return (
    <section className="panel">
      <h2>站點監控</h2>
      <div className="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>站點名稱</th>
              <th>可用車輛</th>
              <th>容量</th>
              <th>座標</th>
            </tr>
          </thead>
          <tbody>
            {stations.map((s) => (
              <tr key={s.id}>
                <td>{s.id}</td>
                <td>{s.name}</td>
                <td>{s.availableVehicles}</td>
                <td>{s.capacity}</td>
                <td>
                  {s.latitude}, {s.longitude}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </section>
  );
}
