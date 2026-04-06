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

struct MyPushHandler: WidgetPushHandler {
    func pushTokenDidChange(_ pushInfo: WidgetPushInfo, widgets: [WidgetInfo]) {
        Task {
            await sendLatest(pushInfo, widgets: widgets)
        }
    }

    private func sendLatest(_ pushInfo: WidgetPushInfo, widgets: [WidgetInfo]) async {
        let hex = pushInfo.token.map { String(format: "%02x", $0) }.joined()

        guard let url = URL(string: "https://magic-ui.com/KumWidgets/receive_token.php") else { return }
        var req = URLRequest(url: url)
        req.httpMethod = "POST"
        req.setValue("application/json", forHTTPHeaderField: "Content-Type")
        req.httpBody = try? JSONSerialization.data(withJSONObject: [
            "token": hex,
            "kinds": widgets.map(\.kind)
        ])

        do {
            let (_, response) = try await URLSession.shared.data(for: req)
            if let http = response as? HTTPURLResponse {
                print("Token upload status:", http.statusCode)
            }
        } catch {
            print("Token upload failed:", error)
        }
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

