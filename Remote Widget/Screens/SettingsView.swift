//
//  SettingsView.swift
//  Remote Widget
//
//  Created by Peter Popovec on 04/07/2026.
//

import SwiftUI
import MagicUI

struct SettingsView: View {
    @ObservedObject private var env = AppEnvironment.env
    @ObservedObject private var appState = AppState.shared

    var body: some View {
        Form {
            Section {
                Picker("Kum Mode", selection: env.stringBinding(for: "kumMode", default: "petres")) {
                    Text("Petres").tag("petres")
                    Text("Lukes").tag("lukes")
                }
            }

            Section(footer: Text("Access only for Kumove")) {
                LabeledContent("Version", value: "\(Bundle.main.shortVersion) (\(Bundle.main.buildNumber))")
                LabeledContent("DeviceId", value: UIDevice.current.identifierForVendor?.uuidString ?? "N/A")
            }
        }
        .navigationTitle("Settings")
        .onChange(of: env.value(for: "kumMode")?.stringValue) {
            appState.widgetPreviewReloadToken = UUID()
        }
    }
}

private extension Bundle {
    var shortVersion: String { infoDictionary?["CFBundleShortVersionString"] as? String ?? "?" }
    var buildNumber: String { infoDictionary?["CFBundleVersion"] as? String ?? "?" }
}
