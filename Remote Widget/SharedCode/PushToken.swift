//
//  PushToken.swift
//  Remote Widget
//
//  Created by Peter Popovec on 06/04/2026.
//

import UIKit
import Darwin

struct PushTokenPayload: Codable {
    let pushToken: String
    let deviceUUID: String
    let bundleID: String
    let deviceModel: String
    let osVersion: String
    let appVersion: String
}

enum PushTokenUploader {

    private static func machineIdentifier() -> String {
        var size = 0
        sysctlbyname("hw.machine", nil, &size, nil, 0)
        var machine = [CChar](repeating: 0, count: size)
        sysctlbyname("hw.machine", &machine, &size, nil, 0)
        return String(cString: machine)
    }

    /// Uploads the APNS push token together with device metadata to the configured endpoint.
    static func upload(pushToken: Data) {
        let tokenString = pushToken.map { String(format: "%02x", $0) }.joined()

        let payload = PushTokenPayload(
            pushToken: tokenString,
            deviceUUID: UIDevice.current.identifierForVendor?.uuidString ?? UUID().uuidString,
            bundleID: Bundle.main.bundleIdentifier ?? "",
            deviceModel: machineIdentifier(),
            osVersion: UIDevice.current.systemVersion,
            appVersion: Bundle.main.object(forInfoDictionaryKey: "CFBundleShortVersionString") as? String ?? ""
        )

        guard let url = URL(string: Config.pushTokenURL),
              let body = try? JSONEncoder().encode(payload) else { return }

        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = body

        URLSession.shared.dataTask(with: request).resume()
    }
}

