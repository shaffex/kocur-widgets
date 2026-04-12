//
//  PushSender.swift
//  Remote Widget
//
//  Created by Peter Popovec on 10/04/2026.
//

import SwiftUI
import MagicUiFramework

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

struct SxAction_sendMessage: SxActionProtocol {
    let node: MagicNode?

    func execute(_ actionString: String) {
        let vars = SxMagicVariables.shared
        let msgTitle = vars.value(forKey: "msgTitle") as? String ?? ""
        let msgBody  = vars.value(forKey: "msgBody")  as? String ?? ""
        let msgSound = vars.value(forKey: "msgSound") as? String ?? "default"

        Task { @MainActor in
            
            PluginActions.shared.runAction("playSystemSound:1004\\dismissView:viewId:sheetViewNewMessage")
            
            //return()
            
            do {
                try await PushSender.send(title: msgTitle, body: msgBody, sound: msgSound)
                PluginActions.shared.runAction("reloadView:viewId:chatView")
            } catch {
                SxMagicVariables.shared.setValue("Failed: \(error.localizedDescription)", forKey: "msgError")
                PluginActions.shared.runAction("delay:1.0:presentAlert:alertMessageFailed")
            }
        }
    }
}

enum VariableUpdater {
    static func set(key: String, value: String, user: String) async throws {
        guard let url = URL(string: "https://magic-ui.com/KumWidgets/api.php") else {
            throw URLError(.badURL)
        }

        let payload: [String: Any] = [
            "action": "set_variable",
            "user":   user,
            "key":    key,
            "value":  value
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

        let (_, response) = try await URLSession.shared.data(for: request)
        guard let http = response as? HTTPURLResponse, (200..<300).contains(http.statusCode) else {
            throw URLError(.badServerResponse)
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

struct SxAction_updateWidgetsOnAllDevices: SxActionProtocol {
    let node: MagicNode?

    func execute(_ actionString: String) {
        Task { @MainActor in
            do {
                try await WidgetUpdater.triggerReload()
            } catch {
                SxMagicVariables.shared.setValue("Failed: \(error.localizedDescription)", forKey: "widgetUpdateError")
            }
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

struct SxAction_logStatusUpdate: SxActionProtocol {
    let node: MagicNode?

    func execute(_ actionString: String) {
        let vars = SxMagicVariables.shared
        let status     = vars.value(forKey: "kocurStatus")     as? String ?? ""
        let emojis     = vars.value(forKey: "kocurEmojis")     as? String ?? ""
        let isNewVideo = vars.value(forKey: "kocurIsNewVideo") as? String ?? "false"

        guard !status.isEmpty else { return }

        Task { @MainActor in
            do {
                try await StatusHistory.log(
                    status: status,
                    emojis: emojis,
                    isNewVideo: isNewVideo == "true" || isNewVideo == "1", user: BusinessLogic.shared.currentUser
                )
            } catch {
                SxMagicVariables.shared.setValue("Failed: \(error.localizedDescription)", forKey: "statusError")
            }
        }
    }
}

struct SxAction_setVariable: SxActionProtocol {
    let node: MagicNode?

    func execute(_ actionString: String) {
        // Expected format: setVariable:<KEY>:<VALUE>
        // or read from magic variables "varKey" / "varValue"
        let parts = actionString.split(separator: ":", maxSplits: 2, omittingEmptySubsequences: false).map(String.init)

        let key: String
        let value: String
        if parts.count == 2 {
            key   = parts[0]
            value = parts[1]
        } else {
            return
        }

        guard !key.isEmpty else { return }

        Task { @MainActor in
            do {
                try await VariableUpdater.set(key: key, value: value, user: BusinessLogic.shared.currentUser)
                SxMagicVariables.shared.setValue(value, forKey: key)
            } catch {
                SxMagicVariables.shared.setValue("Failed: \(error.localizedDescription)", forKey: "varError")
            }
        }
    }
}
