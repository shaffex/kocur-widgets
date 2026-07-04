//
//  WidgetsView.swift
//  Remote Widget
//
//  Created by Peter Popovec on 04/07/2026.
//

import SwiftUI
import MagicUI
import AudioToolbox

struct WidgetsView: View {
    @ObservedObject private var env = AppEnvironment.env
    @ObservedObject private var appState = AppState.shared

    @State private var statusError: String?
    @State private var widgetUpdateError: String?

    // MagicUI caches parsed `url:` content by the exact URL string (no public
    // bypass for a top-level `MagicUiView(url:)`), so a refresh has to change
    // the URL itself to force a real re-fetch.
    private var previewURL: String {
        "\(BusinessLogic.shared.widgetURL)?family=systemSmall&mode=preview&_r=\(appState.widgetPreviewReloadToken.uuidString)"
    }

    var body: some View {
        List {
            Section("Small Widget") {
                VStack(alignment: .leading) {
                    Text("Kocur Emojis")
                        .foregroundStyle(.secondary)
                    TextField("Type Kocur new emojis", text: env.stringBinding(for: "kocurEmojis", default: "🐈‍⬛"))
                        .disableAutocorrection(true)
                }

                VStack(alignment: .leading) {
                    Text("Kocur Status")
                        .foregroundStyle(.secondary)
                    TextField("Type Kocur new status", text: env.stringBinding(for: "kocurStatus"), axis: .vertical)
                        .textInputAutocapitalization(.never)
                        .disableAutocorrection(true)
                }

                Toggle("New Video", isOn: env.boolBinding(for: "kocurIsNewVideo"))

                HStack {
                    Spacer()
                    MagicUiView(url: previewURL, environment: env)
                        .frame(width: 170, height: 170)
                        .background(Color(.systemBackground))
                        .clipShape(RoundedRectangle(cornerRadius: 16))
                        .id(appState.widgetPreviewReloadToken)
                    Spacer()
                }
                .listRowBackground(Color.brown)

                Button("Update Kocur Status") {
                    AudioServicesPlaySystemSound(4095)
                    let status = env.value(for: "kocurStatus")?.stringValue ?? ""
                    let emojis = env.value(for: "kocurEmojis")?.stringValue ?? ""
                    let isNewVideo = env.value(for: "kocurIsNewVideo")?.boolValue ?? false
                    Task {
                        if let error = await KocurStatusUpdater.update(status: status, emojis: emojis, isNewVideo: isNewVideo) {
                            statusError = error
                        } else {
                            statusError = nil
                            appState.widgetPreviewReloadToken = UUID()
                        }
                    }
                }
                .buttonStyle(.borderedProminent)
            }
        }
        .navigationTitle("Widgets")
        .hideKeyboardOnTap()
        .onAppear {
            appState.widgetPreviewReloadToken = UUID()
        }
        .toolbar {
            ToolbarItem(placement: .navigationBarTrailing) {
                Menu("Options", systemImage: "ellipsis") {
                    Button("Refresh Widget preview") {
                        appState.widgetPreviewReloadToken = UUID()
                    }
                    Button("Edit in Browser") {
                        let urlString = "https://magic-ui.com/KumWidgets/index.html?user=\(BusinessLogic.shared.currentUser)&family=systemSmall"
                        if let url = URL(string: urlString) {
                            UIApplication.shared.open(url)
                        }
                    }
                    Button("Update Widgets on all devices") {
                        Task {
                            widgetUpdateError = await KocurStatusUpdater.updateAllDevices()
                        }
                    }
                }
            }
        }
    }
}
