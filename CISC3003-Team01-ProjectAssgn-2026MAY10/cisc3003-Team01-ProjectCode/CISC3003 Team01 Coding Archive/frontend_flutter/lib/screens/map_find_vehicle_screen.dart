import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import '../services/api_service.dart';

class MapFindVehicleScreen extends StatefulWidget {
  const MapFindVehicleScreen({super.key});

  @override
  State<MapFindVehicleScreen> createState() => _MapFindVehicleScreenState();
}

class _MapFindVehicleScreenState extends State<MapFindVehicleScreen> {
  bool _loading = true;
  String _error = '';
  List<dynamic> _stations = [];
  String _vehicleType = '';

  @override
  void initState() {
    super.initState();
    _loadStations();
  }

  Future<void> _loadStations() async {
    setState(() {
      _loading = true;
      _error = '';
    });
    try {
      final result = await ApiService.fetchStations(vehicleType: _vehicleType);
      setState(() => _stations = result);
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      setState(() => _loading = false);
    }
  }

  List<Marker> _buildMarkers() {
    return _stations.map((station) {
      final lat = double.tryParse(station['latitude'].toString()) ?? 22.1300;
      final lng = double.tryParse(station['longitude'].toString()) ?? 113.5500;
      final available = int.tryParse(station['availableVehicles'].toString()) ?? 0;

      return Marker(
        width: 110,
        height: 60,
        point: LatLng(lat, lng),
        child: Column(
          children: [
            Icon(
              Icons.location_on,
              color: available > 0 ? Colors.green : Colors.red,
              size: 32,
            ),
            Text(
              station['name'].toString(),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 11),
            ),
          ],
        ),
      );
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            children: [
              const Text('車型：'),
              const SizedBox(width: 8),
              DropdownButton<String>(
                value: _vehicleType,
                items: const [
                  DropdownMenuItem(value: '', child: Text('全部')),
                  DropdownMenuItem(value: 'bicycle', child: Text('自行車')),
                  DropdownMenuItem(value: 'scooter', child: Text('滑板車')),
                ],
                onChanged: (val) {
                  setState(() => _vehicleType = val ?? '');
                  _loadStations();
                },
              ),
              const Spacer(),
              IconButton(
                onPressed: _loadStations,
                icon: const Icon(Icons.refresh),
              ),
            ],
          ),
        ),
        Expanded(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : _error.isNotEmpty
                  ? Center(child: Text('錯誤：$_error'))
                  : FlutterMap(
                      options: const MapOptions(
                        initialCenter: LatLng(22.1300, 113.5500),
                        initialZoom: 15,
                      ),
                      children: [
                        TileLayer(
                          urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                          userAgentPackageName: 'com.um.rental.app',
                        ),
                        MarkerLayer(markers: _buildMarkers()),
                      ],
                    ),
        ),
        SizedBox(
          height: 180,
          child: ListView.builder(
            itemCount: _stations.length,
            itemBuilder: (context, index) {
              final s = _stations[index];
              return ListTile(
                dense: true,
                leading: const Icon(Icons.store_mall_directory_outlined),
                title: Text(s['name'].toString()),
                subtitle: Text('可用車輛：${s['availableVehicles']} / 容量：${s['capacity']}'),
              );
            },
          ),
        ),
      ],
    );
  }
}
