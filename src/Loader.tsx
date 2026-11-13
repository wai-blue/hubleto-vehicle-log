import App from '@hubleto/react-ui/core/App'
import TableVehicles from './Components/FC/TableVehicles'

class VehicleLogApp extends App {
  init() {
    super.init();
    globalThis.hubleto.registerReactComponent('VehicleLogTableVehicles', TableVehicles);
  }
}

globalThis.hubleto.registerApp('Hubleto/App/External/WaiBlue/VehicleLog', new VehicleLogApp());
