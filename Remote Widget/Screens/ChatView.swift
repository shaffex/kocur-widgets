//
//  ChatView.swift
//  Remote Widget
//
//  Created by Peter Popovec on 04/07/2026.
//

import SwiftUI
import MagicUI
import Combine

struct ChatView: View {
    @ObservedObject private var env = AppEnvironment.env
    @ObservedObject private var appState = AppState.shared

    // MagicUI caches parsed `url:` content by the exact URL string (no public
    // bypass for a top-level `MagicUiView(url:)`), so refreshing has to change
    // the URL itself to force a real re-fetch.
    private var chatHistoryURL: String {
        "https://magic-ui.com/KumWidgets/PushNotifications/chatHistory.php?_r=\(appState.chatReloadToken.uuidString)"
    }

    var body: some View {
        MagicUiView(url: chatHistoryURL, environment: env)
            .id(appState.chatReloadToken)
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button {
                        appState.chatReloadToken = UUID()
                    } label: {
                        Image(systemName: "arrow.clockwise")
                    }
                }
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button {
                        env.setBool(true, for: "viewNewMessage")
                    } label: {
                        Image(systemName: "square.and.pencil")
                    }
                }
            }
    }
}
