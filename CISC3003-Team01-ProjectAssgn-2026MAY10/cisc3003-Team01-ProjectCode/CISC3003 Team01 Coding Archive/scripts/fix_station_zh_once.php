<?php
declare(strict_types=1);
/** One-shot repair for rental_stations.name_zh_cn / name_zh_tw (run from CLI). */

$pdo = new PDO(
    'mysql:host=127.0.0.1;port=3306;dbname=um_rental_system;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$rows = [
    [1, '科研大楼 (N21)', '科研大樓 (N21)'],
    [2, '综合体育馆 (N8)', '綜合體育館 (N8)'],
    [3, '伍宜孙图书馆 (E2)', '伍宜孫圖書館 (E2)'],
    [4, '中央教学楼 (E6)', '中央教學樓 (E6)'],
    [5, '科技学院楼 (E11)', '科技學院樓 (E11)'],
    [6, '人文社科楼 (E21)', '人文社科樓 (E21)'],
    [7, '学生活动中心 (E31)', '學生活動中心 (E31)'],
    [8, '荟萃坊 (S8)', '薈萃坊 (S8)'],
];

$st = $pdo->prepare('UPDATE rental_stations SET name_zh_cn = ?, name_zh_tw = ? WHERE id = ?');
foreach ($rows as $r) {
    $st->execute([$r[1], $r[2], $r[0]]);
}

echo "Updated " . count($rows) . " rental_stations rows.\n";
