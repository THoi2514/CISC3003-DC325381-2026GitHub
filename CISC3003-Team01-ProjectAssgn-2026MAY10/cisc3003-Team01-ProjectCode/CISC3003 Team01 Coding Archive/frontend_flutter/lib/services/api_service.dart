import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  // Android Emulator 可用 10.0.2.2；實機請改成你的電腦 LAN IP
  static const String baseUrl = 'http://10.0.2.2/project/api';

  static Future<List<dynamic>> fetchStations({
    String vehicleType = '',
    String brand = '',
    int? stationId,
  }) async {
    final query = <String, String>{};
    if (vehicleType.isNotEmpty) query['vehicleType'] = vehicleType;
    if (brand.isNotEmpty) query['brand'] = brand;
    if (stationId != null) query['stationID'] = stationId.toString();

    final uri = Uri.parse('$baseUrl/stations.php').replace(queryParameters: query);
    final response = await http.get(uri, headers: {'Accept': 'application/json'});
    final data = jsonDecode(response.body) as Map<String, dynamic>;

    if (response.statusCode >= 400 || data['success'] != true) {
      throw Exception(data['message'] ?? '載入站點失敗');
    }

    return (data['data'] as List<dynamic>? ?? []);
  }

  static Future<Map<String, dynamic>> startRental({
    required int vehicleId,
    required int startStationId,
  }) async {
    final uri = Uri.parse('$baseUrl/start_rental.php');
    final response = await http.post(
      uri,
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'vehicleID': vehicleId,
        'startStationID': startStationId,
      }),
    );

    final data = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode >= 400 || data['success'] != true) {
      throw Exception(data['message'] ?? '開始租賃失敗');
    }
    return data;
  }
}
