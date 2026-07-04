//
//  WidgetPushTokenUploader.swift
//  WidgetExtension
//

import WidgetKit
import UIKit
import Darwin

struct MagicUiWidgetPushHandler: WidgetPushHandler {
    init() {}

    func pushTokenDidChange(_ pushInfo: WidgetPushInfo, widgets: [WidgetInfo]) {
        Task {
            await WidgetPushTokenUploader.upload(pushInfo, widgets: widgets)
        }
    }
}

enum WidgetPushTokenUploader {
    static func upload(_ pushInfo: WidgetPushInfo, widgets: [WidgetInfo]) async {
        let tokenHex = pushInfo.token.map { String(format: "%02x", $0) }.joined()

        var seen = Set<String>()
        let widgetConfigs: [[String: Any]] = widgets.compactMap { widget in
            guard !seen.contains(widget.kind) else { return nil }
            seen.insert(widget.kind)

            let intent = widget.widgetConfigurationIntent(of: ConfigurationAppIntent.self)
            return [
                "kind":            widget.kind,
                "family":          "\(widget.family)",
                "widgetURL":       intent?.widgetURL ?? "(not configured)",
                "refreshInterval": intent?.refreshInterval ?? -1,
                "configured":      intent != nil
            ]
        }

        let payload: [String: Any] = [
            "pushToken":     tokenHex,
            "deviceUUID":    UIDevice.current.identifierForVendor?.uuidString ?? UUID().uuidString,
            "bundleID":      Bundle.main.bundleIdentifier ?? "",
            "deviceModel":   machineIdentifier(),
            "osVersion":     UIDevice.current.systemVersion,
            "appVersion":    Bundle.main.object(forInfoDictionaryKey: "CFBundleShortVersionString") as? String ?? "",
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
                print("Widget push token upload status:", http.statusCode)
            }
        } catch {
            print("Widget push token upload failed:", error)
        }
    }

    private static func machineIdentifier() -> String {
        var size = 0
        sysctlbyname("hw.machine", nil, &size, nil, 0)
        var machine = [CChar](repeating: 0, count: size)
        sysctlbyname("hw.machine", &machine, &size, nil, 0)
        return String(cString: machine)
    }
}
