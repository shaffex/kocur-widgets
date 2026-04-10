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
        guard let url = URL(string: Config.sendPushURL) else {
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
    
    var title: String {
        SxMagicVariables.shared.value(forKey: "msgTitle") as? String ?? "title"
    }
    
    var body: String {
        SxMagicVariables.shared.value(forKey: "msgBody") as? String ?? "title"
    }
    
    var sound: String {
        SxMagicVariables.shared.value(forKey: "msgSound") as? String ?? "title"
    }
    
    func execute(_ actionString: String) {
        //sending = true
        //defer { sending = false }
        
        Task { @MainActor in
            
//            PluginActions.shared.runAction("dismissView:viewId:sheetViewNewMessage")
//            PluginActions.shared.runAction("reloadView:viewId:chatView")
//            return ()
            
            do {
                try await PushSender.send(title: title, body: body, sound: sound)
                //status = "Sent"
                PluginActions.shared.runAction("dismissView:viewId:sheetViewNewMessage")
                PluginActions.shared.runAction("reloadView:viewId:chatView")
            } catch {
                //status = "Failed: \(error.localizedDescription)"
                SxMagicVariables.shared.setValue("Failed: \(error.localizedDescription)", forKey: "msgError")
                
                PluginActions.shared.runAction("dismissView:viewId:sheetViewNewMessage")
                PluginActions.shared.runAction("delay:1.0:presentAlert:alertMessageFailed")
            }
            
        }
    }
}
