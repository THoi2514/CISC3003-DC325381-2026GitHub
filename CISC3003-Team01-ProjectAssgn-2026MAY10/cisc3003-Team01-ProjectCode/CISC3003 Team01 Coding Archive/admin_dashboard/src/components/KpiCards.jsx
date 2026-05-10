export default function KpiCards({ kpi }) {
  const cards = [
    { label: '站點總數', value: kpi.totalStations },
    { label: '可用車輛', value: kpi.totalAvailable },
    { label: '總容量', value: kpi.totalCapacity },
    { label: '可用率', value: `${kpi.usageRate}%` },
  ];

  return (
    <section className="kpi-grid">
      {cards.map((card) => (
        <article className="kpi-card" key={card.label}>
          <p className="kpi-label">{card.label}</p>
          <h3 className="kpi-value">{card.value}</h3>
        </article>
      ))}
    </section>
  );
}
