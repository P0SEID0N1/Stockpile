import AppKit
import SwiftUI
import PortfolioAppleUI

final class AppDelegate: NSObject, NSApplicationDelegate {
    func applicationDidFinishLaunching(_ notification: Notification) {
        NSApp.setActivationPolicy(.regular)
        NSApp.applicationIconImage = BrandIcon.make()
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
            Image(systemName: "chart.line.uptrend.xyaxis")
                .font(.system(size: 28, weight: .semibold))
                .foregroundStyle(Color.accentColor)
            Text("Stockpile")
                .font(.title2.bold())
            Text("The primary settings experience lives inside the app sidebar under Settings, including portfolio reset and server configuration.")
                .foregroundStyle(.secondary)
        }
        .padding(24)
        .frame(width: 420)
    }
}

private enum BrandIcon {
    static func make(size: CGFloat = 512) -> NSImage {
        let image = NSImage(size: NSSize(width: size, height: size))

        image.lockFocus()

        let rect = NSRect(x: 0, y: 0, width: size, height: size)
        let rounded = NSBezierPath(roundedRect: rect, xRadius: size * 0.22, yRadius: size * 0.22)
        let gradient = NSGradient(colors: [
            NSColor(calibratedRed: 0.04, green: 0.23, blue: 0.57, alpha: 1),
            NSColor(calibratedRed: 0.10, green: 0.63, blue: 1.0, alpha: 1),
        ])!
        gradient.draw(in: rounded, angle: 315)

        NSColor(calibratedWhite: 0.93, alpha: 1).setFill()
        drawBar(x: size * 0.19, y: size * 0.29, width: size * 0.11, height: size * 0.22)
        drawBar(x: size * 0.36, y: size * 0.23, width: size * 0.11, height: size * 0.33)
        drawBar(x: size * 0.53, y: size * 0.17, width: size * 0.11, height: size * 0.44)

        let line = NSBezierPath()
        line.move(to: NSPoint(x: size * 0.20, y: size * 0.58))
        line.line(to: NSPoint(x: size * 0.38, y: size * 0.69))
        line.line(to: NSPoint(x: size * 0.52, y: size * 0.64))
        line.line(to: NSPoint(x: size * 0.78, y: size * 0.84))
        line.lineWidth = size * 0.08
        line.lineCapStyle = .round
        line.lineJoinStyle = .round
        NSColor.white.setStroke()
        line.stroke()

        let arrow = NSBezierPath()
        arrow.move(to: NSPoint(x: size * 0.70, y: size * 0.84))
        arrow.line(to: NSPoint(x: size * 0.84, y: size * 0.84))
        arrow.line(to: NSPoint(x: size * 0.84, y: size * 0.70))
        arrow.lineWidth = size * 0.08
        arrow.lineCapStyle = .round
        arrow.lineJoinStyle = .round
        arrow.stroke()

        image.unlockFocus()
        return image
    }

    private static func drawBar(x: CGFloat, y: CGFloat, width: CGFloat, height: CGFloat) {
        let path = NSBezierPath(roundedRect: NSRect(x: x, y: y, width: width, height: height), xRadius: width * 0.28, yRadius: width * 0.28)
        path.fill()
    }
}
