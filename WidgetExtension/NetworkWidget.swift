//
//  NetworkWidget.swift
//  Remote Widget
//
//  Created by Peter Popovec on 02/04/2026.
//

import SwiftUI
import WidgetKit
import AppIntents
import MagicWidget
import UIKit
import Darwin

struct MyPushHandler: WidgetPushHandler {
    func pushTokenDidChange(_ pushInfo: WidgetPushInfo, widgets: [WidgetInfo]) {
        Task {
            await uploadWidgetPushToken(pushInfo, widgets: widgets)
        }
    }

    private func uploadWidgetPushToken(_ pushInfo: WidgetPushInfo, widgets: [WidgetInfo]) async {
        let tokenHex = pushInfo.token.map { String(format: "%02x", $0) }.joined()

        // Deduplicate by kind, extract config if available
        var seen = Set<String>()
        let widgetConfigs: [[String: Any]] = widgets.compactMap { widget in
            guard !seen.contains(widget.kind) else { return nil }
            seen.insert(widget.kind)

            let intent = widget.configuration as? MyCustomNetworkWidgetIntent
            return [
                "kind":            widget.kind,
                "deviceId":        intent?.deviceId        ?? "(not configured)",
                "refreshInterval": intent?.refreshInterval ?? -1,
                "widgetURL":       intent?.widgetURL       ?? "(not configured)",
                "configured":      intent != nil
            ]
        }

        let payload: [String: Any] = [
            "pushToken":    tokenHex,
            "deviceUUID":   UIDevice.current.identifierForVendor?.uuidString ?? UUID().uuidString,
            "bundleID":     Bundle.main.bundleIdentifier ?? "",
            "deviceModel":  machineIdentifier(),
            "osVersion":    UIDevice.current.systemVersion,
            "appVersion":   Bundle.main.object(forInfoDictionaryKey: "CFBundleShortVersionString") as? String ?? "",
            "widgetConfigs": widgetConfigs
        ]

        guard let url = URL(string: Config.widgetPushTokenURL),
              let body = try? JSONSerialization.data(withJSONObject: payload) else { return }

        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = body

        do {
            let (_, response) = try await URLSession.shared.data(for: request)
            if let http = response as? HTTPURLResponse {
                print("Widget token upload status:", http.statusCode)
            }
        } catch {
            print("Widget token upload failed:", error)
        }
    }

    private func machineIdentifier() -> String {
        var size = 0
        sysctlbyname("hw.machine", nil, &size, nil, 0)
        var machine = [CChar](repeating: 0, count: size)
        sysctlbyname("hw.machine", &machine, &size, nil, 0)
        return String(cString: machine)
    }
}


struct MyCustomNetworkWidget: Widget {
    let kind: String = "MyCustomNetworkWidget"

    private static let xmlSnapshotView = """
        <body>
            <vstack>
                <text font="largeTitle">🐈‍⬛ 🐈‍⬛</text>
                <text>Kumovsky Widget</text>
            </vstack>
        </body>
        """
    
    var body: some WidgetConfiguration {
        AppIntentConfiguration(
            kind: kind,
            intent: MyCustomNetworkWidgetIntent.self,
            provider: GenericNetworkWidgetProvider<MyCustomNetworkWidgetIntent>(xmlSnapshotView: Self.xmlSnapshotView)
        ) { entry in
            NetworkWidgetView(entry: entry, kind: kind)
                //.containerBackground(.clear, for: .widget) // This is handled by magicui

            
        }
        //.containerBackgroundRemovable(true) // do not override this
        
        //.contentMarginsDisabled() // removes default widgets margins and can render edge to edge
        
        .configurationDisplayName("Kumovsky Widget")
        .description("Configurable network widget (only for Kum's devices)")
        .supportedFamilies([.systemSmall, .systemMedium, .systemLarge])
        .promptsForUserConfiguration()
        .pushHandler(MyPushHandler.self)
    }
}

// Each widget kind must have its own distinct AppIntent type so WidgetKit can
// associate the "Edit Widget" configuration with the correct widget.
struct MyCustomNetworkWidgetIntent: MagicNetworkWidgetConfigurationIntent {
    static var title: LocalizedStringResource = "MyCustomWidget Configuration"

    @Parameter(title: "Device ID", default: "CustomKocur")
    var deviceId: String

    @Parameter(title: "Refresh Interval (min)", default: 30)
    var refreshInterval: Int

    @Parameter(title: "Widget URL", default: "https://magic-ui.com/Skusky/Widgets/remoteWidget.xml")
    var widgetURL: String?
}

