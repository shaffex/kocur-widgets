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
        MagicUiView.installActionPlugin(name: "updateVariablesToServer", plugin: SxAction_updateVariablesToServer.self)
        
        MagicUiView.installActionPlugin(name: "reloadAllTimelines", plugin: SxAction_reloadAllTimelines.self)
        
        // live activity
        MagicUiView.installActionPlugin(name: "startLiveActivity", plugin: SxAction_startLiveActivity.self)
        MagicUiView.installActionPlugin(name: "updateLiveActivity", plugin: SxAction_updateLiveActivity.self)
    }
    
    var body: some View {
        MagicUiView(resource: "Main")
            .onFirstAppear {
                SxEnvironmentObject.shared.setValue("fff", forKey: "DEVICE_UUID")
                SxMagicVariables.shared.setValue("MIW", forKey: "DEVICE_UUID")
                //SxMagicVariables.shared.setValue("MIW", forKey: "DEVICE_UUID")
                
                // we can update only when view is loaded
                DispatchQueue.main.asyncAfter(deadline: .now() + 0.01) {
                    SxEnvironmentObject.shared.setValue( UIDevice.current.identifierForVendor?.uuidString ?? "N/A", forKey: "DEVICE_UUID")
                }
            }
    }
}

#Preview {
    ContentView()
}
