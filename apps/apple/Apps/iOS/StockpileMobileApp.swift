import SwiftUI
import PortfolioAppleUI

@main
struct StockpileMobileApp: App {
    @State private var model = PortfolioAppModel()

    var body: some Scene {
        WindowGroup {
            PortfolioMobileRootView(model: model)
        }
    }
}
