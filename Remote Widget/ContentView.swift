//
//  ContentView.swift
//  Remote Widget
//
//  Created by Peter Popovec on 02/04/2026.
//

import SwiftUI
import MagicUiFramework

struct ContentView: View {
    init() {
        MagicUiView.installActionPlugin(name: "reloadAllTimelines", plugin: SxAction_reloadAllTimelines.self)
        
        // live activity
        MagicUiView.installActionPlugin(name: "startLiveActivity", plugin: SxAction_startLiveActivity.self)
        MagicUiView.installActionPlugin(name: "updateLiveActivity", plugin: SxAction_updateLiveActivity.self)
    }
    
    var body: some View {
        MagicUiView(resource: "Main")
    }
}

#Preview {
    ContentView()
}
