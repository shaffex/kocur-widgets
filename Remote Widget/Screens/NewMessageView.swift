//
//  NewMessageView.swift
//  Remote Widget
//
//  Created by Peter Popovec on 04/07/2026.
//

import SwiftUI
import AudioToolbox
import MagicUI

struct NewMessageView: View {
    @ObservedObject private var env = AppEnvironment.env
    @ObservedObject private var appState = AppState.shared
    @Environment(\.dismiss) private var dismiss

    private let sounds = ["default"] + (1...9).map { "meow\($0).wav" }

    var body: some View {
        NavigationStack {
            Form {
                VStack(alignment: .leading) {
                    Text("Message Title")
                        .foregroundStyle(.secondary)
                    TextField("Type Message Title", text: env.stringBinding(for: "msgTitle"))
                        .disableAutocorrection(true)
                }

                VStack(alignment: .leading) {
                    Text("Message Text")
                        .foregroundStyle(.secondary)
                    TextField("Type Message Text", text: env.stringBinding(for: "msgBody"), axis: .vertical)
                        .disableAutocorrection(true)
                }

                Picker("Miw Sound", selection: env.stringBinding(for: "msgSound", default: "default")) {
                    ForEach(sounds, id: \.self) { sound in
                        Text(sound == "default" ? "default" : sound.replacingOccurrences(of: ".wav", with: ""))
                            .tag(sound)
                    }
                }

                Button("Send message to 🐈‍⬛") {
                    send()
                }
            }
            .navigationTitle("Message to 🐈‍⬛")
        }
        .presentationDetents([.medium, .large])
    }

    private func send() {
        AudioServicesPlaySystemSound(1004)
        let title = env.value(for: "msgTitle")?.stringValue ?? ""
        let body = env.value(for: "msgBody")?.stringValue ?? ""
        let sound = env.value(for: "msgSound")?.stringValue ?? "default"

        dismiss()

        Task {
            if let error = await MessageSender.send(title: title, body: body, sound: sound) {
                try? await Task.sleep(nanoseconds: 1_000_000_000)
                appState.messageError = error
            } else {
                appState.chatReloadToken = UUID()
            }
        }
    }
}
