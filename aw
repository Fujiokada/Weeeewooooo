local DiscordLib = loadstring(game:HttpGet("https://raw.githubusercontent.com/dawid-scripts/UI-Libs/main/discord%20lib.txt"))()

-- UI Setup
local win = DiscordLib:Window("Macro Recorder")
local main = win:Server("Main", "")
local recordTab = main:Channel("Recorder")

-- Variables
local Recording = {}
local IsRecording = false
local StartTime = 0
local RecordingFileName = "recording.json"
local GitHubToken = "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"
local Repository = "Fujiokada/Random"

-- Utility Functions
local function startRecording()
    IsRecording = true
    StartTime = os.clock()
    Recording = {}
    DiscordLib:Notification("Notification", "Recording Started!", "Okay!")
end

local function stopRecording()
    IsRecording = false
    DiscordLib:Notification("Notification", "Recording Stopped!", "Okay!")
end

local function saveToGitHub(filename)
    local json = game:GetService("HttpService"):JSONEncode(Recording)
    local url = "https://api.github.com/repos/" .. Repository .. "/contents/" .. filename
    local encodedContent = game:GetService("HttpService"):UrlEncode(json)
    
    local response = http.request({
        Url = url,
        Method = "PUT",
        Headers = {
            ["Authorization"] = "token " .. GitHubToken,
            ["Content-Type"] = "application/json",
        },
        Body = game:GetService("HttpService"):JSONEncode({
            message = "Add recording file",
            content = game:GetService("HttpService"):Base64Encode(json), -- Encoding content to Base64
        }),
    })
    
    if response.Success then
        print("[DEBUG] Recording uploaded to GitHub! URL:", response.Body)
        DiscordLib:Notification("Notification", "Recording uploaded to GitHub!", "Okay!")
    else
        print("[ERROR] GitHub upload failed:", response.StatusMessage)
        DiscordLib:Notification("Error", "Failed to upload to GitHub.", "Okay!")
    end
end

local function loadFromGitHub(filename)
    local url = "https://api.github.com/repos/" .. Repository .. "/contents/" .. filename
    
    local response = http.request({
        Url = url,
        Method = "GET",
        Headers = {
            ["Authorization"] = "token " .. GitHubToken,
        },
    })
    
    if response.Success then
        local responseData = game:GetService("HttpService"):JSONDecode(response.Body)
        local decodedContent = game:GetService("HttpService"):JSONDecode(game:GetService("HttpService"):Base64Decode(responseData.content))
        Recording = decodedContent
        DiscordLib:Notification("Notification", "Recording loaded from GitHub!", "Okay!")
    else
        print("[ERROR] Failed to load recording from GitHub:", response.StatusMessage)
        DiscordLib:Notification("Error", "Failed to load recording from GitHub.", "Okay!")
    end
end

local function replayRecording()
    for _, entry in ipairs(Recording) do
        task.wait(entry.TimeFired)
        local remote = game:GetService("Workspace"):FindFirstChild(entry.Remote) -- Adjust search as needed
        if remote and remote[entry.Method] then
            remote[entry.Method](remote, unpack(entry.Arguments))
        end
    end
    DiscordLib:Notification("Notification", "Replay Completed!", "Okay!")
end

-- Metatable Hook for Recording Remotes
local function hookRemotes()
    local mt = getrawmetatable(game)
    local oldNamecall = mt.__namecall
    setreadonly(mt, false)

    mt.__namecall = function(self, ...)
        local method = getnamecallmethod()
        local args = {...}

        if IsRecording and (method == "FireServer" or method == "InvokeServer") then
            table.insert(Recording, {
                Remote = tostring(self),
                Method = method,
                Arguments = args,
                TimeFired = os.clock() - StartTime,
            })
        end

        return oldNamecall(self, ...)
    end
    setreadonly(mt, true)
end

-- UI Buttons
recordTab:Button("Start Recording", function()
    startRecording()
end)

recordTab:Button("Stop Recording", function()
    stopRecording()
end)

recordTab:Textbox("Filename", "Enter filename here...", false, function(input)
    RecordingFileName = input
end)

recordTab:Button("Save to GitHub", function()
    saveToGitHub(RecordingFileName)
end)

recordTab:Button("Load from GitHub", function()
    loadFromGitHub(RecordingFileName)
end)

recordTab:Button("Replay Recording", function()
    replayRecording()
end)

-- Hook the remotes when the script runs
hookRemotes()
DiscordLib:Notification("Notification", "Macro Recorder Ready!", "Okay!")
