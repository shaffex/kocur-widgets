//
//  StatusView.swift
//  Remote Widget
//
//  Created by Peter Popovec on 04/07/2026.
//

import SwiftUI
import MagicUI

struct StatusView: View {
    @ObservedObject private var env = AppEnvironment.env
    @State private var reloadToken = UUID()

    // MagicUI caches parsed `url:` content by the exact URL string (no public
    // bypass for a top-level `MagicUiView(url:)`), so the refresh button has
    // to change the URL itself to force a real re-fetch.
    private var statusHistoryURL: String {
        "https://magic-ui.com/KumWidgets/statusHistory.php?_r=\(reloadToken.uuidString)"
    }

    var body: some View {
        MagicUiView(url: statusHistoryURL, environment: env)
            .id(reloadToken)
            .onAppear {
                reloadToken = UUID()
            }
            .toolbar {
                ToolbarItem(placement: .navigationBarTrailing) {
                    Button("Refresh") {
                        reloadToken = UUID()
                    }
                }
            }
    }
}
