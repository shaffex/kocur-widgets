//
//  HomeView.swift
//  Remote Widget
//
//  Created by Peter Popovec on 04/07/2026.
//

import SwiftUI
import WidgetKit

struct HomeView: View {
    var body: some View {
        VStack {
            Text("🐈‍⬛")
                .font(.system(size: 96))

            Button("RELOAD WIDGET") {
                WidgetCenter.shared.reloadAllTimelines()
            }

            Button("START LIVE ACTIVITY (test)") {
                Task { await LiveActivityStarter.start() }
            }
        }
        .padding()
        .navigationTitle("Home")
    }
}
