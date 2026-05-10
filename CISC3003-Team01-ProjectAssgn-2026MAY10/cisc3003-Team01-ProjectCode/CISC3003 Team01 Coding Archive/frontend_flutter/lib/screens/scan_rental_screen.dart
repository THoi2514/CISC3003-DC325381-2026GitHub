import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../services/api_service.dart';

class ScanRentalScreen extends StatefulWidget {
  const ScanRentalScreen({super.key});

  @override
  State<ScanRentalScreen> createState() => _ScanRentalScreenState();
}

class _ScanRentalScreenState extends State<ScanRentalScreen> {
  bool _isSubmitting = false;
  bool _scannerEnabled = true;
  String _message = '請掃描車輛 QR Code（內容格式：vehicleID:startStationID）';

  Future<void> _handleScan(String rawValue) async {
    if (_isSubmitting) return;

    final parts = rawValue.split(':');
    if (parts.length != 2) {
      setState(() => _message = 'QR 格式錯誤，應為 vehicleID:startStationID');
      return;
    }

    final vehicleId = int.tryParse(parts[0]) ?? 0;
    final stationId = int.tryParse(parts[1]) ?? 0;

    if (vehicleId <= 0 || stationId <= 0) {
      setState(() => _message = 'QR 內容無效（ID 必須 > 0）');
      return;
    }

    setState(() {
      _isSubmitting = true;
      _scannerEnabled = false;
      _message = '租賃請求送出中...';
    });

    try {
      final result = await ApiService.startRental(
        vehicleId: vehicleId,
        startStationId: stationId,
      );
      setState(() => _message = '成功：${result['message']} (OrderID: ${result['data']['orderID']})');
    } catch (e) {
      setState(() => _message = '失敗：$e');
    } finally {
      setState(() {
        _isSubmitting = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Expanded(
          child: ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: MobileScanner(
              fit: BoxFit.cover,
              controller: MobileScannerController(
                detectionSpeed: DetectionSpeed.noDuplicates,
                facing: CameraFacing.back,
                torchEnabled: false,
              ),
              onDetect: (capture) {
                if (!_scannerEnabled) return;
                final code = capture.barcodes.first.rawValue;
                if (code == null || code.trim().isEmpty) return;
                _handleScan(code.trim());
              },
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(_message),
              const SizedBox(height: 10),
              FilledButton(
                onPressed: () {
                  setState(() {
                    _scannerEnabled = true;
                    _message = '已重啟掃碼，請掃描車輛 QR Code。';
                  });
                },
                child: const Text('重新掃碼'),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
