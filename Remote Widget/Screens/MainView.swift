//
//  MainView.swift
//  Remote Widget
//
//  Created by Peter Popovec on 04/07/2026.
//

import SwiftUI
import MagicUI

struct MainView: View {
    @ObservedObject private var env = AppEnvironment.env
    @ObservedObject private var appState = AppState.shared

    var body: some View {
        TabView(selection: env.stringBinding(for: "selectedTab", default: "Home")) {
            NavigationStack {
                HomeView()
            }
            .tabItem { Label("Home", systemImage: "house") }
            .tag("Home")

            NavigationStack {
                ChatView()
            }
            .tabItem { Label("Messenger", systemImage: "bubble.left") }
            .tag("Messages")

            NavigationStack {
                StatusView()
            }
            .tabItem { Label("Status", systemImage: "circle") }
            .tag("Status")

            NavigationStack {
                WidgetsView()
            }
            .tabItem { Label("Widgets", systemImage: "square.grid.2x2") }
            .tag("Widgets")

            NavigationStack {
                SettingsView()
            }
            .tabItem { Label("Settings", systemImage: "gear") }
            .tag("Settings")
        }
        .sheet(isPresented: env.boolBinding(for: "viewNewMessage")) {
            NewMessageView()
        }
        .alert(
            "Sending Failed",
            isPresented: Binding(
                get: { appState.messageError != nil },
                set: { presented in if !presented { appState.messageError = nil } }
            )
        ) {
            Button("OK", role: .cancel) {}
        } message: {
            Text(appState.messageError ?? "")
        }
    }
}
