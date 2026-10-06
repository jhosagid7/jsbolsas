import 'package:flutter_test/flutter_test.dart';
import 'package:bolsas_mobile/main.dart';

void main() {
  testWidgets('App renders login screen smoke test', (WidgetTester tester) async {
    await tester.pumpWidget(const BolsasApp());
    expect(find.text('JSBolsas Pro'), findsOneWidget);
  });
}
