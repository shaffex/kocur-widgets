//
//  LiveActivity.swift
//  Remote Widget
//
//  Created by Peter Popovec on 05/04/2026.
//

import WidgetKit
import SwiftUI
import ActivityKit

import MagicUiFramework

struct SimpleLiveActivityWidget: Widget {
    var body: some WidgetConfiguration {
        ActivityConfiguration(for: ActivityData.self) { context in
            MagicUiWidgetView(string: context.state.xml)
        } dynamicIsland: { context in
            DynamicIsland {
                DynamicIslandExpandedRegion(.center) {
                    Text("Live Activity Expanded Regoin")
                }
            } compactLeading: {
                Text("CL")
            } compactTrailing: {
                Text("CT")
            } minimal: {
                Text("M")
            }
        }
        .supplementalActivityFamilies([.small])
    }
}
