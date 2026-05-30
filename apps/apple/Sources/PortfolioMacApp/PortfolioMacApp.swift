import AppKit
import SwiftUI
import PortfolioAppleUI

final class AppDelegate: NSObject, NSApplicationDelegate {
    func applicationDidFinishLaunching(_ notification: Notification) {
        NSApp.setActivationPolicy(.regular)
        NSApp.activate(ignoringOtherApps: true)
    }
}

@main
struct PortfolioMacApp: App {
    @NSApplicationDelegateAdaptor(AppDelegate.self) private var appDelegate
    @State private var model = PortfolioAppModel()

    var body: some Scene {
        WindowGroup("Stockpile") {
            PortfolioDesktopRootView(model: model)
                .frame(minWidth: 980, minHeight: 680)
        }
        Settings {
            SettingsView()
        }
    }
}

private struct SettingsView: View {
    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Stockpile")
                .font(.title2.bold())
            Text("Configure your server URL and authentication in the future iOS/macOS targets.")
                .foregroundStyle(.secondary)
        }
        .padding(24)
        .frame(width: 420)
    }
}
