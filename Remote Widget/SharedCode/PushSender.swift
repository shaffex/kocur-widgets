//
//  PushSender.swift
//  Remote Widget
//
//  Created by Peter Popovec on 10/04/2026.
//

import Foundation
import WidgetKit

enum PushSender {
    static func send(title: String, body: String, sound: String = "default") async throws {
        guard let url = URL(string: "https://magic-ui.com/KumWidgets/PushNotifications/sendPushNotification.php") else {
            throw URLError(.badURL)
        }

        let payload: [String: Any] = [
            "title": title,
            "body": body,
            "sound": sound
        ]

        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try JSONSerialization.data(withJSONObject: payload)

        let (_, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode) else {
            throw URLError(.badServerResponse)
        }
    }
}

enum MessageSender {
    /// Sends the push notification. Returns an error description on failure, nil on success.
    static func send(title: String, body: String, sound: String) async -> String? {
        do {
            try await PushSender.send(title: title, body: body, sound: sound)
            return nil
        } catch {
            return "Failed: \(error.localizedDescription)"
        }
    }
}

enum VariableUpdater {
    static func update(_ variables: [String: String], user: String = "petres") async throws {
        guard let url = URL(string: "https://magic-ui.com/KumWidgets/api.php") else {
            throw URLError(.badURL)
        }

        let payload: [String: Any] = [
            "action":    "update_variables",
            "user":      user,
            "variables": variables
        ]

        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try JSONSerialization.data(withJSONObject: payload)

        let (data, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode) else {
            throw URLError(.badServerResponse)
        }
        if let json = try JSONSerialization.jsonObject(with: data) as? [String: Any],
           let success = json["success"] as? Bool, !success {
            throw NSError(
                domain: "VariableUpdater",
                code: -1,
                userInfo: [NSLocalizedDescriptionKey: (json["error"] as? String) ?? "Unknown error"]
            )
        }
    }
}

enum WidgetUpdater {
    static func triggerReload(data: [String: String] = [:]) async throws {
        guard let url = URL(string: "https://magic-ui.com/KumWidgets/PushNotifications/widgets/sendWidgetUpdate.php") else {
            throw URLError(.badURL)
        }

        var payload: [String: Any] = [:]
        if !data.isEmpty {
            payload["data"] = data
        }

        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try JSONSerialization.data(withJSONObject: payload)

        let (_, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode) else {
            throw URLError(.badServerResponse)
        }
    }
}

enum StatusHistory {
    static func log(status: String, emojis: String, isNewVideo: Bool, user: String) async throws {
        guard let url = URL(string: "https://magic-ui.com/KumWidgets/statusHistory.php") else {
            throw URLError(.badURL)
        }

        let payload: [String: Any] = [
            "status":     status,
            "emojis":     emojis,
            "isNewVideo": isNewVideo,
            "user":       user
        ]

        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.httpBody = try JSONSerialization.data(withJSONObject: payload)

        let (_, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode) else {
            throw URLError(.badServerResponse)
        }
    }
}

enum KocurStatusUpdater {
    /// Pushes a new status to the server, logs it, and asks widgets to reload.
    /// Returns an error description on failure, nil on success (or on an empty status, a no-op).
    static func update(status: String, emojis: String, isNewVideo: Bool) async -> String? {
        guard !status.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty else { return nil }
        let user = BusinessLogic.shared.currentUser

        do {
            try await VariableUpdater.update([
                "STATUS": status,
                "EMOJIS": emojis,
                "IS_NEWVIDEO": isNewVideo ? "true" : "false"
            ], user: user)

            try await StatusHistory.log(
                status: status,
                emojis: emojis,
                isNewVideo: isNewVideo,
                user: user
            )

            try await WidgetUpdater.triggerReload(data: [
                "user": user,
                "status": status
            ])

            WidgetCenter.shared.reloadAllTimelines()
            return nil
        } catch {
            return "Failed: \(error.localizedDescription)"
        }
    }

    static func updateAllDevices() async -> String? {
        do {
            try await WidgetUpdater.triggerReload()
            return nil
        } catch {
            return "Failed: \(error.localizedDescription)"
        }
    }
}
