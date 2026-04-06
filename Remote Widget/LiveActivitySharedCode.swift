//
//  LiveActivitySharedCode.swift
//  Remote Widget
//
//  Created by Peter Popovec on 05/04/2026.
//

import ActivityKit

struct ActivityData: ActivityAttributes {
    struct ContentState: Codable, Hashable {
        var xml: String
    }
}
